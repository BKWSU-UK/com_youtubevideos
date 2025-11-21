<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_youtubevideos
 *
 * @copyright   (C) 2024 Brahma Kumaris. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \BKWSU\Component\Youtubevideos\Administrator\View\Import\HtmlView $this */

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$typeLabel = Text::_('COM_YOUTUBEVIDEOS_' . strtoupper($this->type));
?>

<form action="<?php echo Route::_('index.php?option=com_youtubevideos&task=import.upload&type=' . $this->type); ?>"
      method="post"
      name="adminForm"
      id="import-form"
      class="form-validate"
      enctype="multipart/form-data">

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h3><?php echo Text::sprintf('COM_YOUTUBEVIDEOS_IMPORT_HEADING', $typeLabel); ?></h3>
                        
                        <div class="alert alert-info">
                            <span class="icon-info-circle" aria-hidden="true"></span>
                            <?php echo Text::sprintf('COM_YOUTUBEVIDEOS_IMPORT_INSTRUCTIONS', $typeLabel); ?>
                        </div>

                        <div class="alert alert-warning">
                            <span class="icon-warning" aria-hidden="true"></span>
                            <?php echo Text::_('COM_YOUTUBEVIDEOS_IMPORT_WARNING'); ?>
                        </div>

                        <?php echo $this->form->renderFieldset('import'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="" />
    <input type="hidden" name="type" value="<?php echo $this->escape($this->type); ?>" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

