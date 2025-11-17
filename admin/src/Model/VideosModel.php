<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Model;

/**
 * Methods supporting a list of videos.
 * This is an alias for FeaturedModel.
 *
 * @since  1.0.0
 */
class VideosModel extends FeaturedModel
{
    /**
     * Method to get the filter form.
     *
     * @param   array    $data      Data.
     * @param   boolean  $loadData  Load current data.
     *
     * @return  \Joomla\CMS\Form\Form|bool  The Form object or false on error.
     *
     * @since   1.0.0
     */
    public function getFilterForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            'com_youtubevideos.videos.filter',
            'filter_featured',
            [
                'control' => '',
                'load_data' => $loadData
            ]
        );

        if (!$form)
        {
            return false;
        }

        return $form;
    }
}



