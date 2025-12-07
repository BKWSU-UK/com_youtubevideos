<?php

namespace BKWSU\Component\Youtubevideos\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * Videos List Model
 *
 * @since  1.0.0
 */
class VideosModel extends ListModel
{
    /**
     * Model context string.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $context = 'com_youtubevideos.videos';

    /**
     * Cached category options for the filter dropdown.
     *
     * @var    array|null
     */
    protected ?array $categoryOptions = null;

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
                'search',
                'tag',
                'category_id',
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
    protected function populateState($ordering = null, $direction = null): void
    {
        $app = Factory::getApplication();

        // Load the parameters
        $params = $app->getParams();
        $this->setState('params', $params);

        // Get playlist_id from menu item
        $playlistId = $app->input->getInt('playlist_id', 0);
        $this->setState('playlist_id', $playlistId);

        // Get category_id from menu item
        $categoryId = $app->input->getInt('category_id', 0);
        $this->setState('category_id', $categoryId);

        // Load filter state
        $search = $app->input->getString('search', '');
        $this->setState('filter.search', $search);

        $filtersInput = $app->input->get('filter', null, 'array');

        $categorySubmitted = false;

        if ($filtersInput !== null) {
            $tagFilter = (int) ($filtersInput['tag'] ?? 0);
            $app->setUserState($this->context . '.filter.tag', $tagFilter);
            $categoryFilter = (int) ($filtersInput['category_id'] ?? 0);
            $app->setUserState($this->context . '.filter.category_id', $categoryFilter);
            $app->setUserState($this->context . '.filter.category_submitted', true);
            $categorySubmitted = true;
        } else {
            $tagFilter = (int) $app->getUserState($this->context . '.filter.tag', 0);
            $categoryFilter = (int) $app->getUserState($this->context . '.filter.category_id', 0);
            $categorySubmitted = (bool) $app->getUserState($this->context . '.filter.category_submitted', false);
        }

        $this->setState('filter.tag', $tagFilter);

        if ($categorySubmitted) {
            $this->setState('filter.category_id', $categoryFilter);
        } elseif ($categoryId > 0) {
            $this->setState('filter.category_id', $categoryId);
        } else {
            // Use default category from params if category filter is shown and no filter submitted
            $showCategoryFilter = (int) $params->get('show_category_filter', 1);
            $defaultCategoryId = $showCategoryFilter ? (int) $params->get('default_category_id', 0) : 0;
            $this->setState('filter.category_id', $defaultCategoryId);
        }

        // Call parent to set up basic pagination state first
        parent::populateState($ordering, $direction);

        // Now override list.limit to be a multiple of videos_per_row
        // Get videos per row from menu parameters
        $videosPerRow = (int) $params->get('videos_per_row', 3);
        
        // Set default limit to 4th multiple (e.g., if 3 per row, default is 12)
        $multiples = [1, 2, 3, 4, 6, 8, 12, 16];
        $defaultLimit = $videosPerRow * $multiples[3]; // 4th item (index 3)
        
        // Adjust list limit to be a multiple of videos_per_row
        $limit = $app->getUserStateFromRequest($this->context . '.list.limit', 'limit', $defaultLimit, 'uint');
        
        // Round up to the nearest multiple of videos_per_row
        if ($limit > 0 && $videosPerRow > 0) {
            $limit = (int) (ceil($limit / $videosPerRow) * $videosPerRow);
        }
        
        $this->setState('list.limit', $limit);

        // Override list.start to support both 'limitstart' and 'start' parameters
        // Priority: limitstart takes precedence over start if both are provided
        // Use sentinel value -1 to detect if parameter was provided
        $limitstart = $app->input->get('limitstart', -1, 'int');
        if ($limitstart === -1) {
            // limitstart not provided, try start parameter
            $limitstart = $app->input->get('start', 0, 'uint');
        } else {
            // Ensure limitstart is non-negative
            $limitstart = max(0, $limitstart);
        }
        $this->setState('list.start', $limitstart);
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  array  The data for the form.
     *
     * @since   1.0.0
     */
    protected function loadFormData(): array
    {
        return [
            'filter' => [
                'search'      => $this->getState('filter.search', ''),
                'category_id' => $this->getState('filter.category_id', 0),
                'tag'         => $this->getState('filter.tag', 0),
            ],
            'list' => [
                'limit' => $this->getState('list.limit', 12),
            ],
        ];
    }

    /**
     * Method to get the filter form.
     *
     * @param   array    $data      Data to bind to the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  \Joomla\CMS\Form\Form|false  A Form object on success, false on failure
     *
     * @since   1.0.0
     */
    public function getFilterForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            $this->context . '.filter',
            'filter_videos',
            [
                'control'   => '',
                'load_data' => $loadData,
            ]
        );

        if (!$form) {
            return false;
        }

        // Get videos per row from menu parameters
        $params = $this->getState('params');
        $videosPerRow = (int) $params->get('videos_per_row', 3);
        
        // Generate custom limit options as multiples of videos_per_row
        $multiples = [1, 2, 3, 4, 6, 8, 12, 16];
        $defaultLimit = $videosPerRow * $multiples[3]; // 4th item
        $optionsXml = '';
        
        foreach ($multiples as $multiplier) {
            $value = $videosPerRow * $multiplier;
            $optionsXml .= '<option value="' . $value . '">' . $value . '</option>';
        }
        
        // Replace the limitbox field with custom options
        $limitFieldXml = '
            <field
                name="limit"
                type="list"
                label="JGLOBAL_LIST_LIMIT"
                default="' . $defaultLimit . '"
                onchange="this.form.submit();"
                class="form-select list-limit"
                >
                ' . $optionsXml . '
            </field>';
        
        $form->setField(new \SimpleXMLElement($limitFieldXml), 'list');

        $showCategoryFilter = (int) $params->get('show_category_filter', 1);

        if (!$showCategoryFilter) {
            $form->removeField('category_id', 'filter');

            return $form;
        }

        $categories = $this->getCategoryOptions();

        if (empty($categories)) {
            $form->removeField('category_id', 'filter');

            return $form;
        }

        $optionsXml = '<option value="">' . Text::_('COM_YOUTUBEVIDEOS_FILTER_CATEGORY_OPTION_ALL') . '</option>';

        foreach ($categories as $category) {
            $title = htmlspecialchars($category->title ?? '', ENT_QUOTES, 'UTF-8');
            $optionsXml .= '<option value="' . (int) $category->id . '">' . $title . '</option>';
        }

        $categoryFieldXml = '
            <field
                name="category_id"
                type="list"
                label="COM_YOUTUBEVIDEOS_FILTER_CATEGORY_LABEL"
                description="COM_YOUTUBEVIDEOS_FILTER_CATEGORY_DESC"
                translate_label="true"
                translate_description="true"
                class="form-select"
                onchange="this.form.submit();"
            >
                ' . $optionsXml . '
            </field>';

        $form->setField(new \SimpleXMLElement($categoryFieldXml), 'filter');

        return $form;
    }

    /**
     * Method to get a list query for videos from database
     *
     * @return  \Joomla\Database\DatabaseQuery  A database query
     *
     * @since   1.0.0
     */
    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        // Select from featured videos table
        $query->select('v.*')
            ->from($db->quoteName('#__youtubevideos_featured', 'v'));

        // Filter by published state
        $query->where($db->quoteName('v.published') . ' = 1');

        // Filter by category (filter selection overrides request parameter)
        $selectedCategoryId = (int) $this->getState('filter.category_id', 0);

        if (!$selectedCategoryId) {
            $selectedCategoryId = (int) $this->getState('category_id', 0);
        }

        if ($selectedCategoryId > 0) {
            $query->where($db->quoteName('v.category_id') . ' = ' . (int) $selectedCategoryId);
        }

        // Filter by playlist if set in menu item
        $playlistId = $this->getState('playlist_id', 0);
        if ($playlistId > 0) {
            $query->where($db->quoteName('v.playlist_id') . ' = ' . (int) $playlistId);
        }

        // Filter by tag
        $tagId = (int) $this->getState('filter.tag', 0);

        if ($tagId > 0) {
            $query->join(
                'INNER',
                $db->quoteName('#__youtubevideos_video_tag_map', 'vtm')
                . ' ON ' . $db->quoteName('vtm.video_id') . ' = ' . $db->quoteName('v.youtube_video_id')
            );
            $query->where($db->quoteName('vtm.tag_id') . ' = ' . $tagId);
            $query->group($db->quoteName('v.id'));
        }

        // Filter by search
        $search = $this->getState('filter.search', '');
        if (!empty($search)) {
            $search = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where(
                '(' . $db->quoteName('v.title') . ' LIKE ' . $search .
                ' OR ' . $db->quoteName('v.description') . ' LIKE ' . $search . ')'
            );
        }

        // Filter by language
        $language = Factory::getLanguage()->getTag();
        $query->whereIn($db->quoteName('v.language'), [$db->quote($language), $db->quote('*')]);

        // Filter by access level
        $user = Factory::getApplication()->getIdentity();
        $groups = $user->getAuthorisedViewLevels();
        $query->whereIn($db->quoteName('v.access'), $groups);

        // Order by ordering and created date
        $query->order($db->quoteName('v.ordering') . ' ASC, ' . $db->quoteName('v.created') . ' DESC');

        return $query;
    }

    /**
     * Method to get videos from database
     *
     * @return  array  Array of video objects
     *
     * @since   1.0.0
     */
    public function getVideos(): array
    {
        $items = $this->getItems();
        
        if (!$items) {
            return [];
        }

        // Convert database items to the format expected by the view
        return $this->normalizeVideos($items);
    }

    /**
     * Normalize database records to a consistent format for the view
     *
     * @param   array  $items  Database records
     *
     * @return  array  Normalized video objects
     *
     * @since   1.0.0
     */
    protected function normalizeVideos(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $video = new \stdClass();

            // Map database fields
            $video->id = $item->id ?? 0;
            $video->videoId = $item->youtube_video_id ?? '';
            $video->title = $item->title ?? '';
            $video->description = $item->description ?? '';
            $video->publishedAt = $item->created ?? '';
            $video->custom_thumbnail = $item->custom_thumbnail ?? null;
            $video->recipe_type = (int) ($item->recipe_type ?? 0);
            $video->isRecipe = $video->recipe_type === 1 && !empty($item->recipe_data);

            // Create thumbnails object from YouTube video ID
            $video->thumbnails = new \stdClass();
            if (!empty($video->videoId)) {
                // Use YouTube thumbnail URLs
                $video->thumbnails->default = (object)[
                    'url' => "https://i.ytimg.com/vi/{$video->videoId}/default.jpg",
                    'width' => 120,
                    'height' => 90
                ];
                $video->thumbnails->medium = (object)[
                    'url' => "https://i.ytimg.com/vi/{$video->videoId}/mqdefault.jpg",
                    'width' => 320,
                    'height' => 180
                ];
                $video->thumbnails->high = (object)[
                    'url' => "https://i.ytimg.com/vi/{$video->videoId}/hqdefault.jpg",
                    'width' => 480,
                    'height' => 360
                ];

                // Use custom thumbnail if available
                if (!empty($item->custom_thumbnail)) {
                    $video->thumbnails->medium = (object)[
                        'url' => $item->custom_thumbnail,
                        'width' => 320,
                        'height' => 180
                    ];
                }
            }

            $normalized[] = $video;
        }

        return $normalized;
    }

    /**
     * Retrieve published categories available to the current user.
     *
     * @return  array
     */
    protected function getCategoryOptions(): array
    {
        if ($this->categoryOptions !== null) {
            return $this->categoryOptions;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([$db->quoteName('id'), $db->quoteName('title')])
            ->from($db->quoteName('#__youtubevideos_categories'))
            ->where($db->quoteName('published') . ' = 1');

        $language = Factory::getLanguage()->getTag();
        $query->whereIn($db->quoteName('language'), [$db->quote($language), $db->quote('*')]);

        $user = Factory::getApplication()->getIdentity();
        $query->whereIn($db->quoteName('access'), $user->getAuthorisedViewLevels());

        $query->order($db->quoteName('ordering') . ' ASC')
            ->order($db->quoteName('title') . ' ASC');

        $this->categoryOptions = $db->setQuery($query)->loadObjectList() ?? [];

        return $this->categoryOptions;
    }
}
 