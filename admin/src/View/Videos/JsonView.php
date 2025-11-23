<?php
namespace BKWSU\Component\Youtubevideos\Administrator\View\Videos;

use Joomla\CMS\MVC\View\JsonView as BaseJsonView;

/**
 * Videos JSON view.
 *
 * @since  1.0.0
 */
class JsonView extends BaseJsonView
{
    /**
     * Execute and display a template.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise an Error object.
     *
     * @since   1.0.0
     */
    public function display($tpl = null)
    {
        $model = $this->getModel();
        $items = $model->getItems();

        // Prepare the data for the response
        $data = [];

        foreach ($items as $item)
        {
            $data[] = [
                'id'   => $item->id,
                'text' => $item->title . ' (' . $item->youtube_video_id . ')'
            ];
        }

        echo json_encode($data);

        return true;
    }
}
