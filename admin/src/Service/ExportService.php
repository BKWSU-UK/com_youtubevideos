<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Service;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\Database\DatabaseInterface;

/**
 * Export Service for YouTube Videos Component
 *
 * @since  1.0.21
 */
class ExportService
{
    /**
     * Database driver
     *
     * @var DatabaseInterface
     */
    private $db;

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
     * Export categories to XML
     *
     * @return  string  XML content
     * @throws  \Exception
     */
    public function exportCategories(): string
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__youtubevideos_categories'))
            ->order($this->db->quoteName('id') . ' ASC');

        $this->db->setQuery($query);
        $categories = $this->db->loadObjectList();

        return $this->generateXML('categories', $categories);
    }

    /**
     * Export playlists to XML
     *
     * @return  string  XML content
     * @throws  \Exception
     */
    public function exportPlaylists(): string
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__youtubevideos_playlists'))
            ->order($this->db->quoteName('id') . ' ASC');

        $this->db->setQuery($query);
        $playlists = $this->db->loadObjectList();

        return $this->generateXML('playlists', $playlists);
    }

    /**
     * Export videos to XML
     *
     * @return  string  XML content
     * @throws  \Exception
     */
    public function exportVideos(): string
    {
        // Export the category alias and YouTube playlist ID as well, so links can be
        // re-created on another site where the numeric ids differ.
        $query = $this->db->getQuery(true)
            ->select('a.*')
            ->select($this->db->quoteName('c.alias', 'category_alias'))
            ->select($this->db->quoteName('p.youtube_playlist_id', 'playlist_youtube_id'))
            ->from($this->db->quoteName('#__youtubevideos_featured', 'a'))
            ->join('LEFT', $this->db->quoteName('#__youtubevideos_categories', 'c'), $this->db->quoteName('c.id') . ' = ' . $this->db->quoteName('a.category_id'))
            ->join('LEFT', $this->db->quoteName('#__youtubevideos_playlists', 'p'), $this->db->quoteName('p.id') . ' = ' . $this->db->quoteName('a.playlist_id'))
            ->order($this->db->quoteName('a.id') . ' ASC');

        $this->db->setQuery($query);
        $videos = $this->db->loadObjectList();

        return $this->generateXML('videos', $videos);
    }

    /**
     * Generate XML from data
     *
     * @param   string  $type  Entity type (categories, playlists, videos)
     * @param   array   $data  Data to export
     *
     * @return  string  XML content
     */
    private function generateXML(string $type, array $data): string
    {
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        // Root element
        $root = $xml->createElement('youtubevideos_export');
        $xml->appendChild($root);

        // Metadata
        $metadata = $xml->createElement('metadata');
        $root->appendChild($metadata);

        $exportDate = $xml->createElement('export_date', Factory::getDate()->toISO8601());
        $metadata->appendChild($exportDate);

        $version = $xml->createElement('version', $this->getComponentVersion());
        $metadata->appendChild($version);

        $typeElement = $xml->createElement('type', $type);
        $metadata->appendChild($typeElement);

        $count = $xml->createElement('count', (string) count($data));
        $metadata->appendChild($count);

        // Data container
        $container = $xml->createElement($type);
        $root->appendChild($container);

        // Singular element name
        $singularType = rtrim($type, 's');
        if ($type === 'categories') {
            $singularType = 'category';
        }

        // Add each item
        foreach ($data as $item) {
            $element = $xml->createElement($singularType);
            $container->appendChild($element);

            foreach ($item as $key => $value) {
                // Skip null values
                if ($value === null) {
                    continue;
                }

                $field = $xml->createElement($key);
                
                // Use CDATA for text fields that might contain special characters
                if (\in_array($key, ['title', 'description', 'params', 'metakey', 'metadesc', 'recipe_data'], true)) {
                    $field->appendChild($xml->createCDATASection(str_replace(']]>', ']]]]><![CDATA[>', (string) $value)));
                } else {
                    // createTextNode escapes the value itself; no htmlspecialchars needed.
                    $field->appendChild($xml->createTextNode((string) $value));
                }
                
                $element->appendChild($field);
            }
        }

        return $xml->saveXML();
    }

    /**
     * Read the installed component version.
     *
     * @return  string
     *
     * @since   1.0.45
     */
    private function getComponentVersion(): string
    {
        $data = Installer::parseXMLInstallFile(JPATH_ADMINISTRATOR . '/components/com_youtubevideos/youtubevideos.xml');

        return (string) ($data['version'] ?? '');
    }
}
