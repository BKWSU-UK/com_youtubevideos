<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Model;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

/**
 * Video model
 *
 * @since  1.0.0
 */
class VideoModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     * @since  1.0.0
     */
    public $typeAlias = 'com_youtubevideos.featured';

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return  Table  A Table object
     *
     * @since   1.0.0
     * @throws  \Exception
     */
    public function getTable($name = 'Featured', $prefix = 'Administrator', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  Form|boolean  A Form object on success, false on failure
     *
     * @since   1.0.0
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm(
            'com_youtubevideos.video',
            'video',
            [
                'control'  => 'jform',
                'load_data' => $loadData,
            ]
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Override to inject tag data for the form.
     *
     * @param   integer  $pk  The primary key value.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.0.16
     */
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item) {
            $item->tags = $this->getVideoTagsAsString($item->youtube_video_id ?? '');
            $this->normaliseRecipeDataForForm($item);
            $this->normaliseParamsForForm($item);
        }

        return $item;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.0.0
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_youtubevideos.edit.video.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        if ($data) {
            $this->normaliseRecipeDataForForm($data);
            $this->normaliseParamsForForm($data);
        }

        $this->preprocessData('com_youtubevideos.video', $data);

        return $data;
    }

    /**
     * Ensure recipe arrays exist for form usage.
     *
     * @param   mixed  $item  Item or data object/array.
     *
     * @return  void
     */
    private function normaliseRecipeDataForForm(&$item): void
    {
        if (!$item) {
            return;
        }

        if (is_array($item)) {
            $recipeData = $item['recipe_data'] ?? null;
        } else {
            $recipeData = $item->recipe_data ?? null;
        }

        if (!empty($recipeData) && is_string($recipeData)) {
            $decoded = json_decode($recipeData, true);

            if (is_array($decoded)) {
                if (is_array($item)) {
                    $item['recipe_ingredients'] = $decoded['ingredients'] ?? [];
                    $item['recipe_method'] = $decoded['method'] ?? [];
                } else {
                    $item->recipe_ingredients = $decoded['ingredients'] ?? [];
                    $item->recipe_method = $decoded['method'] ?? [];
                }
            }
        }

        if (is_array($item)) {
            $item['recipe_ingredients'] = $item['recipe_ingredients'] ?? [];
            $item['recipe_method'] = $item['recipe_method'] ?? [];
        } else {
            $item->recipe_ingredients = $item->recipe_ingredients ?? [];
            $item->recipe_method = $item->recipe_method ?? [];
        }
    }

    /**
     * Convert params to string for textarea display.
     *
     * @param   mixed  $item  Item or data object/array.
     *
     * @return  void
     */
    private function normaliseParamsForForm(&$item): void
    {
        if (!$item) {
            return;
        }

        $params = is_array($item) ? ($item['params'] ?? null) : ($item->params ?? null);

        if ($params instanceof \Joomla\Registry\Registry) {
            $params = $params->toArray();
        }

        if (is_array($params) || is_object($params)) {
            $formatted = json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } elseif (is_string($params)) {
            $trimmed = trim($params);

            if ($trimmed === '') {
                $formatted = '';
            } else {
                $decoded = json_decode($trimmed, true);
                $formatted = json_last_error() === JSON_ERROR_NONE
                    ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    : $params;
            }
        } else {
            $formatted = '';
        }

        if (is_array($item)) {
            $item['params'] = $formatted;
        } else {
            $item->params = $formatted;
        }
    }

    /**
     * Normalise params data for saving.
     *
     * @param   mixed  $params  Params data from form.
     *
     * @return  ?string
     */
    private function normaliseParamsForSave($params): ?string
    {
        if ($params instanceof \Joomla\Registry\Registry) {
            $params = $params->toArray();
        }

        if (is_array($params) || is_object($params)) {
            return json_encode($params, JSON_UNESCAPED_UNICODE);
        }

        if (is_string($params)) {
            $paramsString = trim($params);

            if ($paramsString === '') {
                return null;
            }

            $decoded = json_decode($paramsString, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }

            return json_encode(['value' => $paramsString], JSON_UNESCAPED_UNICODE);
        }

        return null;
    }

    /**
     * Method to save the form data.
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean  True on success, false on failure.
     *
     * @since   1.0.0
     */
    public function save($data)
    {
        $tagsInput = $data['tags'] ?? '';
        unset($data['tags']);

        $recipeIngredients = $data['recipe_ingredients'] ?? null;
        $recipeMethod = $data['recipe_method'] ?? null;
        unset($data['recipe_ingredients']);
        unset($data['recipe_method']);

        $data['params'] = $this->normaliseParamsForSave($data['params'] ?? null);

        if (!empty($data['recipe_type']) && ($recipeIngredients || $recipeMethod)) {
            $recipeData = [];
            
            if (is_array($recipeIngredients)) {
                $ingredients = [];
                foreach ($recipeIngredients as $index => $ingredient) {
                    if (!empty($ingredient['item'])) {
                        $ingredients[] = [
                            'quantity' => $ingredient['quantity'] ?? '',
                            'unit' => $ingredient['unit'] ?? '',
                            'item' => $ingredient['item'],
                            'group' => $ingredient['group'] ?? ''
                        ];
                    }
                }
                $recipeData['ingredients'] = $ingredients;
            }
            
            if (is_array($recipeMethod)) {
                $method = [];
                $stepNum = 1;
                foreach ($recipeMethod as $index => $step) {
                    if (!empty($step['directions'])) {
                        $method[] = [
                            'step' => $stepNum++,
                            'directions' => $step['directions']
                        ];
                    }
                }
                $recipeData['method'] = $method;
            }
            
            $data['recipe_data'] = !empty($recipeData) ? json_encode($recipeData, JSON_UNESCAPED_UNICODE) : null;
        } else {
            $data['recipe_data'] = null;
        }

        $existingVideoId = null;

        if (!empty($data['id'])) {
            $existingVideoId = $this->loadYoutubeVideoId((int) $data['id']);
        }

        $result = parent::save($data);

        if (!$result) {
            return false;
        }

        $pk = (int) ($data['id'] ?? $this->getState($this->getName() . '.id'));
        $currentVideoId = $this->loadYoutubeVideoId($pk);

        $this->syncVideoTags(
            $currentVideoId,
            $this->normaliseTags($tagsInput),
            $existingVideoId
        );

        return true;
    }

    /**
     * Load the YouTube video id for a record.
     *
     * @param   int  $pk  The primary key.
     *
     * @return  string|null
     *
     * @since   1.0.16
     */
    private function loadYoutubeVideoId(int $pk): ?string
    {
        if ($pk <= 0) {
            return null;
        }

        $table = $this->getTable();

        if (!$table->load($pk)) {
            return null;
        }

        return $table->youtube_video_id ?? null;
    }

    /**
     * Turn a comma/newline separated list into a normalised array of tags.
     *
     * @param   mixed  $tagsInput  The raw tags input.
     *
     * @return  array
     *
     * @since   1.0.16
     */
    private function normaliseTags($tagsInput): array
    {
        if (is_array($tagsInput)) {
            $rawTags = $tagsInput;
        } else {
            $rawTags = preg_split('/[\r\n,]+/', (string) $tagsInput);
        }

        if (!$rawTags) {
            return [];
        }

        $tags = [];

        foreach ($rawTags as $tag) {
            $cleanTag = trim(preg_replace('/\s+/', ' ', (string) $tag));

            if ($cleanTag === '') {
                continue;
            }

            $tags[] = $cleanTag;
        }

        return array_values(array_unique($tags));
    }

    /**
     * Retrieve tags for the provided YouTube video ID as a comma-separated list.
     *
     * @param   string|null  $youtubeVideoId  The YouTube ID.
     *
     * @return  string
     *
     * @since   1.0.16
     */
    private function getVideoTagsAsString(?string $youtubeVideoId): string
    {
        $tags = $this->getVideoTags($youtubeVideoId);

        return empty($tags) ? '' : implode(', ', $tags);
    }

    /**
     * Retrieve tags for the provided YouTube video ID.
     *
     * @param   string|null  $youtubeVideoId  The YouTube ID.
     *
     * @return  array
     *
     * @since   1.0.16
     */
    private function getVideoTags(?string $youtubeVideoId): array
    {
        if (empty($youtubeVideoId)) {
            return [];
        }

        try {
            $db = $this->getDatabase();
            $query = $db->getQuery(true)
                ->select($db->quoteName('t.title'))
                ->from($db->quoteName('#__youtubevideos_video_tag_map', 'vtm'))
                ->join(
                    'INNER',
                    $db->quoteName('#__youtubevideos_tags', 't')
                    . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('vtm.tag_id')
                )
                ->where($db->quoteName('vtm.video_id') . ' = :videoId')
                ->where($db->quoteName('t.published') . ' = 1')
                ->bind(':videoId', $youtubeVideoId)
                ->order($db->quoteName('t.title') . ' ASC');

            $db->setQuery($query);

            return (array) $db->loadColumn();
        } catch (\RuntimeException $exception) {
            return [];
        }
    }

    /**
     * Persist tag relations for the video.
     *
     * @param   string|null  $videoId           The YouTube video ID.
     * @param   array        $tags              Normalised list of tag names.
     * @param   string|null  $previousVideoId   The previous YouTube ID (if it changed).
     *
     * @return  void
     *
     * @since   1.0.16
     */
    private function syncVideoTags(?string $videoId, array $tags, ?string $previousVideoId = null): void
    {
        $db = $this->getDatabase();

        if ($previousVideoId && $previousVideoId !== $videoId) {
            $this->removeVideoTags($previousVideoId);
        }

        if (!$videoId) {
            return;
        }

        $this->removeVideoTags($videoId);

        if (empty($tags)) {
            return;
        }

        foreach ($tags as $tagTitle) {
            $tagId = $this->getOrCreateTagId($tagTitle);

            if (!$tagId) {
                continue;
            }

            try {
                $query = $db->getQuery(true)
                    ->insert($db->quoteName('#__youtubevideos_video_tag_map'))
                    ->columns([$db->quoteName('video_id'), $db->quoteName('tag_id')])
                    ->values(
                        $db->quote($videoId) . ', ' . (int) $tagId
                    );

                $db->setQuery($query);
                $db->execute();
            } catch (\RuntimeException $exception) {
                // Ignore duplicate insert attempts.
            }
        }
    }

    /**
     * Remove tag mappings for a video.
     *
     * @param   string  $videoId  The YouTube video ID.
     *
     * @return  void
     *
     * @since   1.0.16
     */
    private function removeVideoTags(string $videoId): void
    {
        $db = $this->getDatabase();

        try {
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__youtubevideos_video_tag_map'))
                ->where($db->quoteName('video_id') . ' = :videoId')
                ->bind(':videoId', $videoId);

            $db->setQuery($query);
            $db->execute();
        } catch (\RuntimeException $exception) {
            // Ignore delete failures to avoid interrupting save.
        }
    }

    /**
     * Get or create a tag ID for the supplied title.
     *
     * @param   string  $title  The tag title.
     *
     * @return  int|null
     *
     * @since   1.0.16
     */
    private function getOrCreateTagId(string $title): ?int
    {
        if ($title === '') {
            return null;
        }

        $db = $this->getDatabase();
        $identity = Factory::getApplication()->getIdentity();
        $userId = (int) ($identity ? $identity->id : 0);

        try {
            // Try to find an existing tag by title first.
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__youtubevideos_tags'))
                ->where($db->quoteName('title') . ' = :title')
                ->bind(':title', $title);

            $db->setQuery($query);
            $tagId = (int) $db->loadResult();

            if ($tagId) {
                return $tagId;
            }

            $alias = $this->generateUniqueTagAlias($title);
            $now = Factory::getDate()->toSql();

            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__youtubevideos_tags'))
                ->columns(
                    [
                        $db->quoteName('title'),
                        $db->quoteName('alias'),
                        $db->quoteName('description'),
                        $db->quoteName('published'),
                        $db->quoteName('created'),
                        $db->quoteName('created_by'),
                        $db->quoteName('modified'),
                        $db->quoteName('modified_by'),
                        $db->quoteName('hits'),
                    ]
                )
                ->values(
                    implode(
                        ', ',
                        [
                            $db->quote($title),
                            $db->quote($alias),
                            $db->quote(''),
                            1,
                            $db->quote($now),
                            $userId,
                            $db->quote($now),
                            $userId,
                            0,
                        ]
                    )
                );

            $db->setQuery($query);
            $db->execute();

            return (int) $db->insertid();
        } catch (\RuntimeException $exception) {
            return null;
        }
    }

    /**
     * Generate a unique alias for a tag title.
     *
     * @param   string  $title  The tag title.
     *
     * @return  string
     *
     * @since   1.0.16
     */
    private function generateUniqueTagAlias(string $title): string
    {
        $alias = ApplicationHelper::stringURLSafe($title);

        if ($alias === '') {
            $alias = ApplicationHelper::stringURLSafe((string) microtime(true));
        }

        $baseAlias = $alias;
        $suffix = 1;
        $db = $this->getDatabase();

        while (true) {
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__youtubevideos_tags'))
                ->where($db->quoteName('alias') . ' = ' . $db->quote($alias));

            $db->setQuery($query);

            if (!(int) $db->loadResult()) {
                return $alias;
            }

            $alias = $baseAlias . '-' . $suffix++;
        }
    }
}
