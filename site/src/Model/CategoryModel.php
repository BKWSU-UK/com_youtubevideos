<?php

namespace BKWSU\Component\Youtubevideos\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Uri\Uri;
use stdClass;

/**
 * Category Model
 *
 * @since  1.0.0
 */
class CategoryModel extends ListModel
{
    /**
     * Model context string.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $context = 'com_youtubevideos.category';

    /**
     * The category object
     *
     * @var    object
     * @since  1.0.0
     */
    protected $_category = null;

    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @since   1.0.0
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'v.id',
                'title', 'v.title',
                'created', 'v.created',
                'ordering', 'v.ordering',
                'published', 'v.published',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function populateState($ordering = 'v.ordering', $direction = 'ASC'): void
    {
        $app = Factory::getApplication();

        // Load the category id
        $pk = $app->input->getInt('id');
        $this->setState('category.id', $pk);

        // Load the parameters
        $params = $app->getParams();
        $this->setState('params', $params);

        // List limit honouring menu parameter
        $videosPerPage = (int) $params->get('videos_per_page', $app->get('list_limit'));
        if ($videosPerPage <= 0) {
            $videosPerPage = (int) $app->get('list_limit', 12);
        }

        // Support both limitstart and start query parameters
        $limitstart = $app->input->get('limitstart', -1, 'int');
        if ($limitstart === -1) {
            $limitstart = $app->input->get('start', 0, 'uint');
        } else {
            $limitstart = max(0, $limitstart);
        }

        // Handle search filter
        $filtersInput = $app->input->get('filter', null, 'array');

        if ($filtersInput !== null) {
            $search = trim((string) ($filtersInput['search'] ?? ''));
            $app->setUserState($this->context . '.filter.search', $search);
        } else {
            $search = $app->getUserState($this->context . '.filter.search', '');
        }

        $this->setState('filter.search', $search);

        // Filter by published state
        $this->setState('filter.published', 1);

        // Filter by language
        $this->setState('filter.language', Multilanguage::isEnabled());

        // List state information
        parent::populateState($ordering, $direction);

        $this->setState('list.limit', $videosPerPage);
        $this->setState('list.start', $limitstart);
    }

    /**
     * Method to get a database query to list items.
     *
     * @return  \Joomla\Database\DatabaseQuery  A database query object.
     *
     * @since   1.0.0
     */
    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        // Select required fields from the videos table
        $query->select('v.*')
            ->from($db->quoteName('#__youtubevideos_featured', 'v'));

        // Filter by category
        $categoryId = (int) $this->getState('category.id');
        if ($categoryId) {
            $query->where($db->quoteName('v.category_id') . ' = :category_id')
                ->bind(':category_id', $categoryId, \Joomla\Database\ParameterType::INTEGER);
        }

        // Filter by published state
        $published = (int) $this->getState('filter.published');
        if ($published) {
            $query->where($db->quoteName('v.published') . ' = 1');
        }

        // Filter by language
        if ($this->getState('filter.language')) {
            $query->whereIn($db->quoteName('v.language'), [Factory::getLanguage()->getTag(), '*'], \Joomla\Database\ParameterType::STRING);
        }

        // Join with statistics for view counts
        $query->select($db->quoteName('s.views', 'views'))
            ->select($db->quoteName('s.likes', 'likes'))
            ->leftJoin($db->quoteName('#__youtubevideos_statistics', 's') . ' ON ' . $db->quoteName('s.youtube_video_id') . ' = ' . $db->quoteName('v.youtube_video_id'));

        // Add the list ordering clause
        $orderCol = $this->state->get('list.ordering', 'v.ordering');
        $orderDirn = $this->state->get('list.direction', 'ASC');

        $query->order($db->quoteName($orderCol) . ' ' . $db->escape($orderDirn));

        // Apply search filter
        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $token = '%' . $db->escape($search, true) . '%';
            $query->where(
                '(' . $db->quoteName('v.title') . ' LIKE :searchTitle OR ' .
                $db->quoteName('v.description') . ' LIKE :searchDesc)'
            )
                ->bind(':searchTitle', $token)
                ->bind(':searchDesc', $token);
        }

        return $query;
    }

    /**
     * Method to get category data for the current category
     *
     * @return  object|boolean  The category object or false on failure.
     *
     * @since   1.0.0
     */
    public function getCategory()
    {
        if ($this->_category === null) {
            $pk = $this->getState('category.id');

            try {
                $db = $this->getDatabase();
                $query = $db->getQuery(true);

                $query->select('c.*')
                    ->from($db->quoteName('#__youtubevideos_categories', 'c'))
                    ->where($db->quoteName('c.id') . ' = :id')
                    ->bind(':id', $pk, \Joomla\Database\ParameterType::INTEGER);

                // Filter by published state
                if ($this->getState('filter.published')) {
                    $query->where($db->quoteName('c.published') . ' = 1');
                }

                // Filter by language
                if ($this->getState('filter.language')) {
                    $query->whereIn($db->quoteName('c.language'), [Factory::getLanguage()->getTag(), '*'], \Joomla\Database\ParameterType::STRING);
                }

                $db->setQuery($query);
                $this->_category = $db->loadObject();

                if (empty($this->_category)) {
                    throw new \Exception(\Joomla\CMS\Language\Text::_('COM_YOUTUBEVIDEOS_ERROR_CATEGORY_NOT_FOUND'), 404);
                }
            } catch (\Exception $e) {
                $this->setError($e->getMessage());
                $this->_category = false;
            }
        }

        return $this->_category;
    }

    /**
     * Gets the list of videos for the category with normalized thumbnail data.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getItems()
    {
        $items = parent::getItems();

        if (empty($items)) {
            return [];
        }

        return $this->normalizeVideos($items);
    }

    /**
     * Normalizes raw database items to include thumbnail metadata similar to videos view.
     *
     * @param   array  $items  Database rows.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    protected function normalizeVideos(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $video = clone $item;
            $video->videoId = $item->videoId ?? $item->video_id ?? $item->youtube_video_id ?? '';
            $video->custom_thumbnail = $this->resolveCustomThumbnail($item->custom_thumbnail ?? null);
            $video->recipe_type = (int) ($item->recipe_type ?? 0);
            $video->recipe_data = $item->recipe_data ?? null;
            $video->isRecipe = $video->recipe_type === 1 && !empty($video->recipe_data);

            $video->thumbnails = $this->buildThumbnailSet($video->videoId, $video->custom_thumbnail);
            $video->thumbnail_url = $this->determinePrimaryThumbnail($video);

            $normalized[] = $video;
        }

        return $normalized;
    }

    /**
     * Builds a consistent thumbnails object for a video.
     *
     * @param   string|null  $videoId          The YouTube video ID
     * @param   string|null  $customThumbnail  Custom thumbnail path/URL
     *
     * @return  stdClass
     */
    protected function buildThumbnailSet(?string $videoId, ?string $customThumbnail): stdClass
    {
        $thumbnails = new stdClass();

        if (!empty($videoId)) {
            $thumbnails->default = (object) [
                'url' => $this->buildYoutubeThumbnailUrl($videoId, 'default'),
                'width' => 120,
                'height' => 90,
            ];
            $thumbnails->medium = (object) [
                'url' => $this->buildYoutubeThumbnailUrl($videoId, 'mqdefault'),
                'width' => 320,
                'height' => 180,
            ];
            $thumbnails->high = (object) [
                'url' => $this->buildYoutubeThumbnailUrl($videoId, 'hqdefault'),
                'width' => 480,
                'height' => 360,
            ];
        }

        if (!empty($customThumbnail)) {
            $customObject = (object) [
                'url' => $customThumbnail,
                'width' => 640,
                'height' => 360,
            ];
            $thumbnails->custom = $customObject;
            $thumbnails->medium = $customObject;
            $thumbnails->high = $customObject;
        }

        return $thumbnails;
    }

    /**
     * Determines the preferred thumbnail URL for the card grid.
     *
     * @param   object  $video  Video object
     *
     * @return  string
     */
    protected function determinePrimaryThumbnail(object $video): string
    {
        if (!empty($video->custom_thumbnail)) {
            return $video->custom_thumbnail;
        }

        if (!empty($video->thumbnails?->medium?->url)) {
            return $video->thumbnails->medium->url;
        }

        if (!empty($video->thumbnails?->high?->url)) {
            return $video->thumbnails->high->url;
        }

        if (!empty($video->thumbnails?->default?->url)) {
            return $video->thumbnails->default->url;
        }

        return $this->buildYoutubeThumbnailUrl($video->videoId ?? $video->youtube_video_id ?? '', 'hqdefault');
    }

    /**
     * Builds a YouTube thumbnail URL for a given variant.
     *
     * @param   string  $videoId  YouTube video ID
     * @param   string  $variant  Thumbnail variant (default/hqdefault/mqdefault/etc.)
     *
     * @return  string
     */
    protected function buildYoutubeThumbnailUrl(string $videoId, string $variant): string
    {
        if (empty($videoId)) {
            return '';
        }

        return sprintf('https://i.ytimg.com/vi/%s/%s.jpg', $videoId, $variant);
    }

    /**
     * Normalises custom thumbnail paths to absolute URLs.
     *
     * @param   string|null  $path  Stored thumbnail path
     *
     * @return  string|null
     */
    protected function resolveCustomThumbnail(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $trimmed = trim($path);

        if (preg_match('#^(https?:)?//#i', $trimmed) || str_starts_with($trimmed, 'data:')) {
            return $trimmed;
        }

        return Uri::root() . ltrim($trimmed, '/');
    }

    /**
     * Retrieve the filter form for category view
     *
     * @param   array    $data      Data to bind.
     * @param   boolean  $loadData  Should the form load its own data.
     *
     * @return  \Joomla\CMS\Form\Form|false
     */
    public function getFilterForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            $this->context . '.filter',
            'filter_category',
            [
                'control'   => '',
                'load_data' => $loadData,
            ]
        );

        return $form ?: false;
    }

    /**
     * Get active filters for the category view.
     *
     * @return  array
     */
    public function getActiveFilters(): array
    {
        return [
            'filter.search' => $this->getState('filter.search'),
        ];
    }

    /**
     * Method to increment the hit counter for the category
     *
     * @return  boolean  True if successful; false otherwise and internal error set.
     *
     * @since   1.0.0
     */
    public function hit()
    {
        $pk = (int) $this->getState('category.id');

        if ($pk) {
            $db = $this->getDatabase();
            $query = $db->getQuery(true);

            $query->update($db->quoteName('#__youtubevideos_categories'))
                ->set($db->quoteName('hits') . ' = ' . $db->quoteName('hits') . ' + 1')
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $pk, \Joomla\Database\ParameterType::INTEGER);

            try {
                $db->setQuery($query);
                $db->execute();

                return true;
            } catch (\Exception $e) {
                $this->setError($e->getMessage());
            }
        }

        return false;
    }
}





