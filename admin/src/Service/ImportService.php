<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Service;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;

/**
 * Import Service for YouTube Videos Component
 *
 * @since  1.0.21
 */
class ImportService
{
    /**
     * Database driver
     *
     * @var DatabaseDriver
     */
    private $db;

    /**
     * Import statistics
     *
     * @var array
     */
    private $stats = [
        'added' => 0,
        'skipped' => 0,
        'errors' => []
    ];

    /**
     * Cache for table columns
     *
     * @var array
     */
    private $columnsCache = [];

    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  Database driver
     */
    public function __construct(DatabaseDriver $db = null)
    {
        $this->db = $db ?: Factory::getContainer()->get(DatabaseDriver::class);
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
     * Parse and validate XML file
     *
     * @param   string  $xmlContent  XML content
     *
     * @return  \SimpleXMLElement|false
     */
    public function parseXML(string $xmlContent)
    {
        libxml_use_internal_errors(true);
        
        $xml = simplexml_load_string($xmlContent);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            $errorMessages = [];
            
            foreach ($errors as $error) {
                $errorMessages[] = sprintf(
                    'Line %d: %s',
                    $error->line,
                    trim($error->message)
                );
            }
            
            libxml_clear_errors();
            $this->stats['errors'][] = 'XML Parse Error: ' . implode('; ', $errorMessages);
            
            return false;
        }
        
        // Validate structure
        if (!isset($xml->metadata) || !isset($xml->metadata->type)) {
            $this->stats['errors'][] = 'Invalid XML structure: missing metadata';
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
        $this->resetStats();
        
        if ((string) $xml->metadata->type !== 'categories') {
            $this->stats['errors'][] = 'Invalid XML type: expected categories';
            return $this->stats;
        }
        
        if (!isset($xml->categories->category)) {
            $this->stats['errors'][] = 'No categories found in XML';
            return $this->stats;
        }
        
        foreach ($xml->categories->category as $category) {
            try {
                $this->importCategory($category);
            } catch (\Exception $e) {
                $this->stats['errors'][] = sprintf(
                    'Error importing category "%s": %s',
                    (string) $category->title,
                    $e->getMessage()
                );
            }
        }
        
        return $this->stats;
    }

    /**
     * Import a single category
     *
     * @param   \SimpleXMLElement  $category  Category element
     *
     * @return  void
     */
    private function importCategory(\SimpleXMLElement $category): void
    {
        $alias = (string) $category->alias;
        
        // Check if category already exists
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__youtubevideos_categories'))
            ->where($this->db->quoteName('alias') . ' = :alias')
            ->bind(':alias', $alias);
        
        $this->db->setQuery($query);
        $existingId = $this->db->loadResult();
        
        if ($existingId) {
            $this->stats['skipped']++;
            return;
        }

        $tableColumns = $this->getTableColumns('#__youtubevideos_categories');
        
        // Insert new category
        $columns = [];
        $values = [];
        $bindings = [];
        
        foreach ($category as $key => $value) {
            $strKey = (string) $key;
            $strValue = (string) $value;
            
            // Skip id and auto-generated fields, and check if column exists
            if (in_array($strKey, ['id', 'checked_out', 'checked_out_time']) || !in_array($strKey, $tableColumns)) {
                continue;
            }
            
            $columns[] = $this->db->quoteName($strKey);
            $values[] = ':' . $strKey;
            $bindings[':' . $strKey] = $strValue;
        }
        
        $query = $this->db->getQuery(true)
            ->insert($this->db->quoteName('#__youtubevideos_categories'))
            ->columns($columns)
            ->values(implode(', ', $values));
        
        foreach ($bindings as $key => $value) {
            $query->bind($key, $bindings[$key]);
        }
        
        $this->db->setQuery($query);
        $this->db->execute();
        
        $this->stats['added']++;
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
        $this->resetStats();
        
        if ((string) $xml->metadata->type !== 'playlists') {
            $this->stats['errors'][] = 'Invalid XML type: expected playlists';
            return $this->stats;
        }
        
        if (!isset($xml->playlists->playlist)) {
            $this->stats['errors'][] = 'No playlists found in XML';
            return $this->stats;
        }
        
        foreach ($xml->playlists->playlist as $playlist) {
            try {
                $this->importPlaylist($playlist);
            } catch (\Exception $e) {
                $this->stats['errors'][] = sprintf(
                    'Error importing playlist "%s": %s',
                    (string) $playlist->title,
                    $e->getMessage()
                );
            }
        }
        
        return $this->stats;
    }

    /**
     * Import a single playlist
     *
     * @param   \SimpleXMLElement  $playlist  Playlist element
     *
     * @return  void
     */
    private function importPlaylist(\SimpleXMLElement $playlist): void
    {
        $youtubePlaylistId = (string) $playlist->youtube_playlist_id;
        
        // Check if playlist already exists
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__youtubevideos_playlists'))
            ->where($this->db->quoteName('youtube_playlist_id') . ' = :playlistId')
            ->bind(':playlistId', $youtubePlaylistId);
        
        $this->db->setQuery($query);
        $existingId = $this->db->loadResult();
        
        if ($existingId) {
            $this->stats['skipped']++;
            return;
        }

        $tableColumns = $this->getTableColumns('#__youtubevideos_playlists');
        
        // Insert new playlist
        $columns = [];
        $values = [];
        $bindings = [];
        
        foreach ($playlist as $key => $value) {
            $strKey = (string) $key;
            $strValue = (string) $value;
            
            // Skip id and auto-generated fields, and check if column exists
            if (in_array($strKey, ['id', 'checked_out', 'checked_out_time']) || !in_array($strKey, $tableColumns)) {
                continue;
            }
            
            $columns[] = $this->db->quoteName($strKey);
            $values[] = ':' . $strKey;
            $bindings[':' . $strKey] = $strValue;
        }
        
        $query = $this->db->getQuery(true)
            ->insert($this->db->quoteName('#__youtubevideos_playlists'))
            ->columns($columns)
            ->values(implode(', ', $values));
        
        foreach ($bindings as $key => $value) {
            $query->bind($key, $bindings[$key]);
        }
        
        $this->db->setQuery($query);
        $this->db->execute();
        
        $this->stats['added']++;
    }

    /**
     * Import videos from XML
     *
     * @param   \SimpleXMLElement  $xml  XML element
     *
     * @return  array  Import statistics
     */
    public function importVideos(\SimpleXMLElement $xml): array
    {
        $this->resetStats();
        
        if ((string) $xml->metadata->type !== 'videos') {
            $this->stats['errors'][] = 'Invalid XML type: expected videos';
            return $this->stats;
        }
        
        if (!isset($xml->videos->video)) {
            $this->stats['errors'][] = 'No videos found in XML';
            return $this->stats;
        }
        
        foreach ($xml->videos->video as $video) {
            try {
                $this->importVideo($video);
            } catch (\Exception $e) {
                $this->stats['errors'][] = sprintf(
                    'Error importing video "%s": %s',
                    (string) $video->title,
                    $e->getMessage()
                );
            }
        }
        
        return $this->stats;
    }

    /**
     * Import a single video
     *
     * @param   \SimpleXMLElement  $video  Video element
     *
     * @return  void
     */
    private function importVideo(\SimpleXMLElement $video): void
    {
        $youtubeVideoId = (string) $video->youtube_video_id;
        
        // Check if video already exists
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__youtubevideos_featured'))
            ->where($this->db->quoteName('youtube_video_id') . ' = :videoId')
            ->bind(':videoId', $youtubeVideoId);
        
        $this->db->setQuery($query);
        $existingId = $this->db->loadResult();
        
        if ($existingId) {
            $this->stats['skipped']++;
            return;
        }

        $tableColumns = $this->getTableColumns('#__youtubevideos_featured');
        
        // Insert new video
        $columns = [];
        $values = [];
        $bindings = [];
        
        foreach ($video as $key => $value) {
            $strKey = (string) $key;
            $strValue = (string) $value;
            
            // Skip id and auto-generated fields, and check if column exists
            if (in_array($strKey, ['id', 'checked_out', 'checked_out_time']) || !in_array($strKey, $tableColumns)) {
                continue;
            }
            
            $columns[] = $this->db->quoteName($strKey);
            $values[] = ':' . $strKey;
            $bindings[':' . $strKey] = $strValue;
        }
        
        $query = $this->db->getQuery(true)
            ->insert($this->db->quoteName('#__youtubevideos_featured'))
            ->columns($columns)
            ->values(implode(', ', $values));
        
        foreach ($bindings as $key => $value) {
            $query->bind($key, $bindings[$key]);
        }
        
        $this->db->setQuery($query);
        $this->db->execute();
        
        $this->stats['added']++;
    }

    /**
     * Reset statistics
     *
     * @return  void
     */
    private function resetStats(): void
    {
        $this->stats = [
            'added' => 0,
            'skipped' => 0,
            'errors' => []
        ];
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
            $this->stats['errors'][] = 'JSON Parse Error: ' . json_last_error_msg();
            return false;
        }
        
        if (!isset($data['recipes']) || !is_array($data['recipes'])) {
            $this->stats['errors'][] = 'Invalid JSON structure: missing recipes array';
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
        
        if (!isset($jsonData['recipes']) || !is_array($jsonData['recipes'])) {
            $this->stats['errors'][] = 'No recipes found in JSON';
            return $this->stats;
        }
        
        foreach ($jsonData['recipes'] as $recipe) {
            try {
                $this->importRecipe($recipe);
            } catch (\Exception $e) {
                $this->stats['errors'][] = sprintf(
                    'Error importing recipe "%s": %s',
                    $recipe['title'] ?? 'Unknown',
                    $e->getMessage()
                );
            }
        }
        
        return $this->stats;
    }

    /**
     * Import a single recipe
     *
     * @param   array  $recipe  Recipe data
     *
     * @return  void
     */
    private function importRecipe(array $recipe): void
    {
        $title = $recipe['title'] ?? $recipe['name'] ?? '';
        $description = $recipe['description'] ?? '';
        $youtubeId = $recipe['youtubeId'] ?? '';
        
        $videoId = $this->matchRecipeToVideo($title, $description, $youtubeId);
        
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
            ->bind(':videoId', $videoId, \Joomla\Database\ParameterType::INTEGER);
        
        $this->db->setQuery($query);
        $this->db->execute();
        
        $this->stats['added']++;
    }

    /**
     * Match recipe to existing video using fuzzy matching
     *
     * @param   string  $title        Recipe title
     * @param   string  $description  Recipe description
     * @param   string  $youtubeId    YouTube video ID
     *
     * @return  int|null  Video ID if matched, null otherwise
     */
    private function matchRecipeToVideo(string $title, string $description, string $youtubeId): ?int
    {
        if ($youtubeId) {
            $query = $this->db->getQuery(true)
                ->select($this->db->quoteName('id'))
                ->from($this->db->quoteName('#__youtubevideos_featured'))
                ->where($this->db->quoteName('youtube_video_id') . ' = :youtubeId')
                ->bind(':youtubeId', $youtubeId);
            
            $this->db->setQuery($query);
            $id = $this->db->loadResult();
            
            if ($id) {
                return (int) $id;
            }
        }
        
        $query = $this->db->getQuery(true)
            ->select('id, title, description')
            ->from($this->db->quoteName('#__youtubevideos_featured'));
        
        $this->db->setQuery($query);
        $videos = $this->db->loadObjectList();
        
        $bestMatch = null;
        $bestScore = 0;
        $threshold = 70;
        
        foreach ($videos as $video) {
            $titleScore = $this->calculateSimilarity($title, $video->title);
            
            if ($titleScore > $bestScore && $titleScore >= $threshold) {
                $bestScore = $titleScore;
                $bestMatch = $video->id;
            }
            
            if ($description && $video->description) {
                $descScore = $this->calculateSimilarity($description, $video->description);
                
                if ($descScore > $bestScore && $descScore >= $threshold) {
                    $bestScore = $descScore;
                    $bestMatch = $video->id;
                }
            }
        }
        
        return $bestMatch ? (int) $bestMatch : null;
    }

    /**
     * Calculate similarity between two strings
     *
     * @param   string  $str1  First string
     * @param   string  $str2  Second string
     *
     * @return  float  Similarity percentage (0-100)
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        $str1 = $this->normaliseString($str1);
        $str2 = $this->normaliseString($str2);
        
        if ($str1 === $str2) {
            return 100.0;
        }
        
        $percent = 0;
        similar_text($str1, $str2, $percent);
        
        return $percent;
    }

    /**
     * Normalise string for comparison
     *
     * @param   string  $str  String to normalise
     *
     * @return  string  Normalised string
     */
    private function normaliseString(string $str): string
    {
        $str = mb_strtolower($str);
        $str = preg_replace('/[^a-z0-9\s]/', '', $str);
        $str = preg_replace('/\s+/', ' ', $str);
        
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
            'method' => []
        ];
        
        if (isset($recipe['list']) && is_array($recipe['list']) && !empty($recipe['list'])) {
            $recipe = $recipe['list'][0];
        }
        
        if (isset($recipe['recipe']['ingr']) && is_array($recipe['recipe']['ingr'])) {
            foreach ($recipe['recipe']['ingr'] as $ingredient) {
                $item = $ingredient['Item'] ?? '';
                
                $group = '';
                if (preg_match('/^\{([^}]+)\}(.*)$/', $item, $matches)) {
                    $group = trim($matches[1]);
                    $item = trim($matches[2]);
                }
                
                $recipeData['ingredients'][] = [
                    'quantity' => $ingredient['Qty'] ?? '',
                    'unit' => $ingredient['Unit'] ?? '',
                    'item' => $item,
                    'group' => $group
                ];
            }
        }
        
        if (isset($recipe['recipe']['method']) && is_array($recipe['recipe']['method'])) {
            $stepNum = 1;
            foreach ($recipe['recipe']['method'] as $method) {
                $directions = $method['Directions'] ?? '';
                
                $directions = preg_replace('/^\{[^}]+\}/', '', $directions);
                $directions = trim($directions);
                
                if ($directions) {
                    $recipeData['method'][] = [
                        'step' => $stepNum++,
                        'directions' => $directions
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



