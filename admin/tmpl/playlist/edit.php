<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_youtubevideos
 *
 * @copyright   (C) 2024 BKWSU
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;

/** @var \BKWSU\Component\Youtubevideos\Administrator\View\Playlist\HtmlView $this */

$app = Factory::getApplication();
$input = $app->input;

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');

$this->useCoreUI = true;
?>

<form action="<?php echo Route::_('index.php?option=com_youtubevideos&view=playlist&layout=edit&id=' . (int) $this->item->id); ?>" 
      method="post" 
      name="adminForm" 
      id="playlist-form" 
      class="form-validate"
      aria-label="<?php echo Text::_('COM_YOUTUBEVIDEOS_PLAYLIST_FORM_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>">

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_YOUTUBEVIDEOS_FIELDSET_DETAILS')); ?>
        <div class="row">
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-body">
                        <?php echo $this->form->renderField('title'); ?>
                        <?php echo $this->form->renderField('alias'); ?>
                        <?php echo $this->form->renderField('youtube_playlist_id'); ?>
                        <?php echo $this->form->renderField('description'); ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <?php echo $this->form->renderField('published'); ?>
                        <?php echo $this->form->renderField('access'); ?>
                        <?php echo $this->form->renderField('language'); ?>
                        <?php echo $this->form->renderField('ordering'); ?>
                    </div>
                </div>
                <?php if ($this->item->id) : ?>
                <div class="card mt-3">
                    <div class="card-body">
                        <?php echo $this->form->renderField('created'); ?>
                        <?php echo $this->form->renderField('created_by'); ?>
                        <?php echo $this->form->renderField('modified'); ?>
                        <?php echo $this->form->renderField('modified_by'); ?>
                        <?php echo $this->form->renderField('hits'); ?>
                        <?php echo $this->form->renderField('id'); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <?php echo LayoutHelper::render('joomla.edit.publishingdata', $this); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'metadata', Text::_('JGLOBAL_FIELDSET_METADATA_OPTIONS')); ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <?php echo $this->form->renderField('metakey'); ?>
                        <?php echo $this->form->renderField('metadesc'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="task" value="" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

