<div id="toolbox">
  <h3>{L('admintoolboxlong')} :: {L('tags')}</h3>

  <fieldset class="box">
    <legend>{L('tags')}</legend>
    <?php
    $this->assign('list_type', 'tag');
    $this->assign('rows', $proj->listTags(true));
    $this->display('common.list.tpl');
    ?>
  </fieldset>
</div>
