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
     * Constructor
     *
     * @param   DatabaseDriver  $db  Database driver
     */
    public function __construct(DatabaseDriver $db = null)
    {
        $this->db = $db ?: Factory::getContainer()->get(DatabaseDriver::class);
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
        
        // Insert new category
        $columns = [];
        $values = [];
        $bindings = [];
        
        foreach ($category as $key => $value) {
            $strKey = (string) $key;
            $strValue = (string) $value;
            
            // Skip id and auto-generated fields
            if (in_array($strKey, ['id', 'checked_out', 'checked_out_time'])) {
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
        
        // Insert new playlist
        $columns = [];
        $values = [];
        $bindings = [];
        
        foreach ($playlist as $key => $value) {
            $strKey = (string) $key;
            $strValue = (string) $value;
            
            // Skip id and auto-generated fields
            if (in_array($strKey, ['id', 'checked_out', 'checked_out_time'])) {
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
        
        // Insert new video
        $columns = [];
        $values = [];
        $bindings = [];
        
        foreach ($video as $key => $value) {
            $strKey = (string) $key;
            $strValue = (string) $value;
            
            // Skip id and auto-generated fields
            if (in_array($strKey, ['id', 'checked_out', 'checked_out_time'])) {
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
}



