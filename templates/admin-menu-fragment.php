<div class="navbar-collapse collapse">
    
  <ul class="nav navbar-nav">
    <li class="active"><a href="/admin">Home</a></li>
    <li><a href="/admin/users">Users <span class="badge"><?= @$userCount ?></span></a></li>
    <li><a href="#">Notebooks <span class="badge"><?= @$notebookCount ?></span></a></li>
    <li><a href="#">Notes <span class="badge"><?= @$noteCount ?></span></a></li>
    <li><a href="#">Files <span class="badge"><?= @$fileCount ?></span></a></li>
    <?php if(isset($canAccessClient) && $canAccessClient): ?>
      <li><a href="<?= @$clientUrl ?>/client" target="_blank">Braindump Client</a></li>
    <?php endif; ?>
  </ul>

  <ul class="nav navbar-nav pull-right">
    <li><a href="/admin/users/<?= @$user->id ?>"><?= @$user->name ?></a></li>
    <li><a href="/logout">Logout</a></li>
  </ul>

</div>