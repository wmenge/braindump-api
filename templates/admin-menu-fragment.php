<div class="navbar-collapse collapse">
    
  <ul class="nav navbar-nav">
    <li class="active"><a href="/admin">Home</a></li>
    <li><a href="/admin/users">Users <span class="badge"><?= @$userCount ?></span></a></li>
    <li><a href="#">Notebooks <span class="badge"><?= @$notebookCount ?></span></a></li>
    <li><a href="#">Notes <span class="badge"><?= @$noteCount ?></span></a></li>
    <li><a href="#">Files <span class="badge"><?= @$fileCount ?></span></a></li>
  </ul>

  <ul class="nav navbar-nav pull-right">
    <li><a href="/admin/users/<?= @$user->id ?>"><?= @$user->login ?></a></li>
    <li><a href="/admin/logout">Logout</a></li>
  </ul>

</div>