<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Service;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Import Service for YouTube Videos Component
 *
 * Each import runs in a transaction: if any item fails, nothing is imported.
 *
 * @since  1.0.21
 */
class ImportService
{
    /**
     * Minimum similarity (0-100) for a recipe title to match a video title
     *
     * @var    int
     * @since  1.0.45
     */
    private const RECIPE_MATCH_THRESHOLD = 70;

    /**
     * Columns that are never copied from an import file
     *
     * @var    string[]
     * @since  1.0.45
     */
    private const SKIPPED_COLUMNS = ['id', 'checked_out', 'checked_out_time', 'asset_id', 'hits'];

    /**
     * Database driver
     *
     * @var DatabaseInterface
     */
    private $db;

    /**
     * Import statistics
     *
     * @var array
     */
    private $stats = [
        'added'   => 0,
        'skipped' => 0,
        'errors'  => [],
    ];

    /**
     * Cache for table columns
     *
     * @var array
     */
    private $columnsCache = [];

    /**
     * Cache for resolved foreign keys
     *
     * @var array
     */
    private $lookupCache = [];

    /**
     * Constructor
     *
     * @param   ?DatabaseInterface  $db  Database driver
     */
    public function __construct(?DatabaseInterface $db = null)
    {
        $this->db = $db ?: Factory::getContainer()->get(DatabaseInterface::class);
    }

    /**
     * Parse and validate XML file
     *
     * @param   string  $xmlContent  XML content
     *
     * @return  \SimpleXMLElement|false
     */
    public function parseXML(string $xmlContent)
    {
        $previous = libxml_use_internal_errors(true);

        // LIBXML_NONET: never fetch external resources while parsing.
        $xml = simplexml_load_string($xmlContent, \SimpleXMLElement::class, LIBXML_NONET);

        if ($xml === false) {
            $errorMessages = [];

            foreach (libxml_get_errors() as $error) {
                $errorMessages[] = Text::sprintf('COM_YOUTUBEVIDEOS_IMPORT_ERROR_XML_LINE', $error->line, trim($error->message));
            }

            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            $this->stats['errors'][] = Text::sprintf('COM_YOUTUBEVIDEOS_IMPORT_ERROR_XML_PARSE', implode('; ', $errorMessages));

            return false;
        }

        libxml_use_internal_errors($previous);

        if (!isset($xml->metadata) || !isset($xml->metadata->type)) {
            $this->stats['errors'][] = Text::_('COM_YOUTUBEVIDEOS_IMPORT_ERROR_INVALID_STRUCTURE');

            return false;
        }

        return $xml;
    }

    /**
     * Import categories from XML
     *
     * @param   \SimpleXMLElement  $xml  XML element
     *
     * @return  array  Import statistics
     */
    public function importCategories(\SimpleXMLElement $xml): array
    {
        return $this->importItems($xml, 'categories', 'category', function (\SimpleXMLElement $category): void {
            $alias = (string) $category->alias;

            if ($alias !== '' && $this->findId('#__youtubevideos_categories', 'alias', $alias)) {
                $this->stats['skipped']++;

                return;
            }

            $this->insertRow('#__youtubevideos_categories', $category);
        });
    }

    /**
     * Import playlists from XML
     *
     * @param   \SimpleXMLElement  $xml  XML element
     *
     * @return  array  Import statistics
     */
    public function importPlaylists(\SimpleXMLElement $xml): array
    {
        return $this->importItems($xml, 'playlists', 'playlist', function (\SimpleXMLElement $playlist): void {
            if ($this->findId('#__youtubevideos_playlists', 'youtube_playlist_id', (string) $playlist->youtube_playlist_id)) {
                $this->stats['skipped']++;

                return;
            }

            $this->insertRow('#__youtubevideos_playlists', $playlist);
        });
    }

    /**
     * Import videos from XML
     *
     * Category and playlist links are resolved through the exported category alias and
     * YouTube playlist ID. Files from older versions only contain the source site's numeric
     * ids, which mean nothing on another site, so those links are left empty.
     *
     * @param   \SimpleXMLElement  $xml  XML element
     *
     * @return  array  Import statistics
     */
    public function importVideos(\SimpleXMLElement $xml): array
    {
        return $this->importItems($xml, 'videos', 'video', function (\SimpleXMLElement $video): void {
            if ($this->findId('#__youtubevideos_featured', 'youtube_video_id', (string) $video->youtube_video_id)) {
                $this->stats['skipped']++;

                return;
            }

            $categoryAlias = (string) ($video->category_alias ?? '');
            $playlistId    = (string) ($video->playlist_youtube_id ?? '');

            $overrides = [
                'category_id' => $categoryAlias !== '' ? $this->findId('#__youtubevideos_categories', 'alias', $categoryAlias) : null,
                'playlist_id' => $playlistId !== '' ? $this->findId('#__youtubevideos_playlists', 'youtube_playlist_id', $playlistId) : null,
            ];

            $this->insertRow('#__youtubevideos_featured', $video, $overrides);
        });
    }

    /**
     * Get import statistics
     *
     * @return  array
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Parse and validate JSON file
     *
     * @param   string  $jsonContent  JSON content
     *
     * @return  array|false
     */
    public function parseJSON(string $jsonContent)
    {
        $data = json_decode($jsonContent, true);

        if ($data === null) {
            $this->stats['errors'][] = Text::sprintf('COM_YOUTUBEVIDEOS_IMPORT_ERROR_JSON_PARSE', json_last_error_msg());

            return false;
        }

        if (!isset($data['recipes']) || !\is_array($data['recipes'])) {
            $this->stats['errors'][] = Text::_('COM_YOUTUBEVIDEOS_IMPORT_ERROR_NO_RECIPES');

            return false;
        }

        return $data;
    }

    /**
     * Import recipes from JSON
     *
     * @param   array  $jsonData  Parsed JSON data
     *
     * @return  array  Import statistics
     */
    public function importRecipes(array $jsonData): array
    {
        $this->resetStats();

        if (empty($jsonData['recipes']) || !\is_array($jsonData['recipes'])) {
            $this->stats['errors'][] = Text::_('COM_YOUTUBEVIDEOS_IMPORT_ERROR_NO_RECIPES');

            return $this->stats;
        }

        $videos = $this->loadVideoTitles();

        $this->runInTransaction(function () use ($jsonData, $videos): void {
            foreach ($jsonData['recipes'] as $recipe) {
                try {
                    $this->importRecipe((array) $recipe, $videos);
                } catch (\Exception $e) {
                    $this->stats['errors'][] = Text::sprintf(
                        'COM_YOUTUBEVIDEOS_IMPORT_ERROR_ITEM',
                        $recipe['title'] ?? Text::_('COM_YOUTUBEVIDEOS_IMPORT_UNKNOWN_ITEM'),
                        $e->getMessage()
                    );
                }
            }
        });

        return $this->stats;
    }

    /**
     * Shared loop for the XML imports.
     *
     * @param   \SimpleXMLElement  $xml       The parsed file.
     * @param   string             $type      Expected metadata type and container element.
     * @param   string             $element   The item element name.
     * @param   callable           $importer  Imports one item.
     *
     * @return  array  Import statistics
     *
     * @since   1.0.45
     */
    private function importItems(\SimpleXMLElement $xml, string $type, string $element, callable $importer): array
    {
        $this->resetStats();

        if ((string) $xml->metadata->type !== $type) {
            $this->stats['errors'][] = Text::sprintf('COM_YOUTUBEVIDEOS_IMPORT_ERROR_WRONG_TYPE', Text::_('COM_YOUTUBEVIDEOS_' . strtoupper($type)));

            return $this->stats;
        }

        if (!isset($xml->{$type}->{$element})) {
            $this->stats['errors'][] = Text::sprintf('COM_YOUTUBEVIDEOS_IMPORT_ERROR_NO_ITEMS', Text::_('COM_YOUTUBEVIDEOS_' . strtoupper($type)));

            return $this->stats;
        }

        $this->runInTransaction(function () use ($xml, $type, $element, $importer): void {
            foreach ($xml->{$type}->{$element} as $item) {
                try {
                    $importer($item);
                } catch (\Exception $e) {
                    $this->stats['errors'][] = Text::sprintf('COM_YOUTUBEVIDEOS_IMPORT_ERROR_ITEM', (string) $item->title, $e->getMessage());
                }
            }
        });

        return $this->stats;
    }

    /**
     * Run an import inside a transaction; roll back everything when any item failed.
     *
     * @param   callable  $callback  The import loop.
     *
     * @return  void
     *
     * @since   1.0.45
     */
    private function runInTransaction(callable $callback): void
    {
        $this->db->transactionStart();

        try {
            $callback();
        } catch (\Throwable $e) {
            $this->stats['errors'][] = $e->getMessage();
        }

        if (!empty($this->stats['errors'])) {
            $this->db->transactionRollback();
            $this->stats['added'] = 0;

            return;
        }

        $this->db->transactionCommit();
    }

    /**
     * Insert one exported row, keeping only columns that exist in the target table.
     *
     * @param   string             $table      The table name.
     * @param   \SimpleXMLElement  $row        The exported row.
     * @param   array              $overrides  Column values that replace the exported ones (null = SQL NULL).
     *
     * @return  void
     *
     * @since   1.0.45
     */
    private function insertRow(string $table, \SimpleXMLElement $row, array $overrides = []): void
    {
        $tableColumns = $this->getTableColumns($table);
        $values       = [];

        foreach ($row as $key => $value) {
            $key = (string) $key;

            if (\in_array($key, self::SKIPPED_COLUMNS, true) || !\in_array($key, $tableColumns, true)) {
                continue;
            }

            $values[$key] = (string) $value;
        }

        foreach ($overrides as $key => $value) {
            if (\in_array($key, $tableColumns, true)) {
                $values[$key] = $value;
            }
        }

        if (empty($values)) {
            $this->stats['skipped']++;

            return;
        }

        $query        = $this->db->getQuery(true)->insert($this->db->quoteName($table));
        $columns      = [];
        $placeholders = [];
        $index        = 0;

        foreach ($values as $column => $value) {
            $columns[] = $this->db->quoteName($column);

            if ($value === null) {
                $placeholders[] = 'NULL';
                continue;
            }

            $placeholder    = ':v' . $index++;
            $placeholders[] = $placeholder;
            $query->bind($placeholder, $values[$column]);
        }

        $query->columns($columns)->values(implode(', ', $placeholders));

        $this->db->setQuery($query)->execute();

        $this->stats['added']++;
    }

    /**
     * Find a record id by a unique column value.
     *
     * @param   string  $table   The table name.
     * @param   string  $column  The column to match.
     * @param   string  $value   The value to match.
     *
     * @return  int|null
     *
     * @since   1.0.45
     */
    private function findId(string $table, string $column, string $value): ?int
    {
        if ($value === '') {
            return null;
        }

        $cacheKey = $table . '|' . $column . '|' . $value;

        if (!\array_key_exists($cacheKey, $this->lookupCache)) {
            $query = $this->db->getQuery(true)
                ->select($this->db->quoteName('id'))
                ->from($this->db->quoteName($table))
                ->where($this->db->quoteName($column) . ' = :value')
                ->bind(':value', $value);

            $id = (int) $this->db->setQuery($query, 0, 1)->loadResult();

            $this->lookupCache[$cacheKey] = $id ?: null;
        }

        return $this->lookupCache[$cacheKey];
    }

    /**
     * Get columns for a table from cache or database
     *
     * @param   string  $table  Table name
     *
     * @return  array
     */
    private function getTableColumns(string $table): array
    {
        if (!isset($this->columnsCache[$table])) {
            $this->columnsCache[$table] = array_keys($this->db->getTableColumns($table));
        }

        return $this->columnsCache[$table];
    }

    /**
     * Reset statistics
     *
     * @return  void
     */
    private function resetStats(): void
    {
        $this->stats       = ['added' => 0, 'skipped' => 0, 'errors' => []];
        $this->lookupCache = [];
    }

    /**
     * Import a single recipe
     *
     * @param   array  $recipe  Recipe data
     * @param   array  $videos  Normalised video titles keyed by video id
     *
     * @return  void
     */
    private function importRecipe(array $recipe, array $videos): void
    {
        $title     = (string) ($recipe['title'] ?? $recipe['name'] ?? '');
        $youtubeId = (string) ($recipe['youtubeId'] ?? '');

        $videoId = $this->matchRecipeToVideo($title, $youtubeId, $videos);

        if (!$videoId) {
            $this->stats['skipped']++;

            return;
        }

        $recipeData = $this->extractRecipeData($recipe);

        if (empty($recipeData)) {
            $this->stats['skipped']++;

            return;
        }

        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__youtubevideos_featured'))
            ->set($this->db->quoteName('recipe_type') . ' = 1')
            ->set($this->db->quoteName('recipe_data') . ' = :recipeData')
            ->where($this->db->quoteName('id') . ' = :videoId')
            ->bind(':recipeData', $recipeData)
            ->bind(':videoId', $videoId, ParameterType::INTEGER);

        $this->db->setQuery($query)->execute();

        $this->stats['added']++;
    }

    /**
     * Load all video titles, normalised for matching.
     *
     * @return  array  Map of video id => normalised title (empty titles are left out)
     *
     * @since   1.0.45
     */
    private function loadVideoTitles(): array
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName(['id', 'title']))
            ->from($this->db->quoteName('#__youtubevideos_featured'));

        $titles = [];

        foreach ($this->db->setQuery($query)->loadObjectList() as $video) {
            $normalised = $this->normaliseString((string) $video->title);

            if ($normalised !== '') {
                $titles[(int) $video->id] = $normalised;
            }
        }

        return $titles;
    }

    /**
     * Match a recipe to an existing video: exact YouTube ID first, then fuzzy title match.
     *
     * Descriptions are not compared: they are long (slow to compare) and share boilerplate
     * text, which produced false matches.
     *
     * @param   string  $title      Recipe title
     * @param   string  $youtubeId  YouTube video ID
     * @param   array   $videos     Normalised video titles keyed by video id
     *
     * @return  int|null  Video ID if matched, null otherwise
     */
    private function matchRecipeToVideo(string $title, string $youtubeId, array $videos): ?int
    {
        if ($youtubeId !== '') {
            $id = $this->findId('#__youtubevideos_featured', 'youtube_video_id', $youtubeId);

            if ($id) {
                return $id;
            }
        }

        $needle = $this->normaliseString($title);

        // A title with no letters or digits left cannot be matched reliably.
        if ($needle === '') {
            return null;
        }

        $bestMatch = null;
        $bestScore = 0.0;

        foreach ($videos as $videoId => $videoTitle) {
            if ($videoTitle === $needle) {
                return $videoId;
            }

            similar_text($needle, $videoTitle, $percent);

            if ($percent >= self::RECIPE_MATCH_THRESHOLD && $percent > $bestScore) {
                $bestScore = $percent;
                $bestMatch = $videoId;
            }
        }

        return $bestMatch;
    }

    /**
     * Normalise string for comparison (Unicode aware: keeps letters and digits in any script)
     *
     * @param   string  $str  String to normalise
     *
     * @return  string  Normalised string
     */
    private function normaliseString(string $str): string
    {
        $str = mb_strtolower($str, 'UTF-8');
        $str = (string) preg_replace('/[^\p{L}\p{N}\s]+/u', '', $str);
        $str = (string) preg_replace('/\s+/u', ' ', $str);

        return trim($str);
    }

    /**
     * Extract recipe data from JSON recipe object
     *
     * @param   array  $recipe  Recipe array from JSON
     *
     * @return  string|null  JSON encoded recipe data
     */
    private function extractRecipeData(array $recipe): ?string
    {
        $recipeData = [
            'ingredients' => [],
            'method'      => [],
        ];

        if (isset($recipe['list']) && \is_array($recipe['list']) && !empty($recipe['list'])) {
            $recipe = $recipe['list'][0];
        }

        if (isset($recipe['recipe']['ingr']) && \is_array($recipe['recipe']['ingr'])) {
            foreach ($recipe['recipe']['ingr'] as $ingredient) {
                $item  = (string) ($ingredient['Item'] ?? '');
                $group = '';

                if (preg_match('/^\{([^}]+)\}(.*)$/u', $item, $matches)) {
                    $group = trim($matches[1]);
                    $item  = trim($matches[2]);
                }

                $recipeData['ingredients'][] = [
                    'quantity' => $ingredient['Qty'] ?? '',
                    'unit'     => $ingredient['Unit'] ?? '',
                    'item'     => $item,
                    'group'    => $group,
                ];
            }
        }

        if (isset($recipe['recipe']['method']) && \is_array($recipe['recipe']['method'])) {
            $stepNum = 1;

            foreach ($recipe['recipe']['method'] as $method) {
                $directions = trim((string) preg_replace('/^\{[^}]+\}/u', '', (string) ($method['Directions'] ?? '')));

                if ($directions !== '') {
                    $recipeData['method'][] = [
                        'step'       => $stepNum++,
                        'directions' => $directions,
                    ];
                }
            }
        }

        if (empty($recipeData['ingredients']) && empty($recipeData['method'])) {
            return null;
        }

        return json_encode($recipeData, JSON_UNESCAPED_UNICODE);
    }
}
