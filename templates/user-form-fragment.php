<div class="col-sm-3">
</div>
<div class="col-sm-6">

  <?php if (!empty($user->id)): ?>
    <h3>Edit user</h3>
  <?php else: ?>
    <h3>Create new user</h3>
  <?php endif; ?>

    <div class="tab-pane active" id="user">

     <form role="form" action="/admin/users/<?= @$user->id ?>" method="POST">

      <?php if (!empty($user->id)): ?>
        <input type="hidden" name="_METHOD" value="PUT"/>
      <?php endif; ?>

        <div class="panel panel-default">
        <div class="panel-body">

            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="text" value="<?= @$user->email ?>" class="form-control" id="email" name="email" placeholder="Enter E-mail" required>
            </div>

            <div class="form-group">
                <label for="first_name">First name</label>
                <input type="text" value="<?= @$user->first_name ?>" class="form-control" id="first_name" name="first_name" placeholder="Enter first name" required>
            </div>

            <div class="form-group">
                <label for="last_name">Last name</label>
                <input type="text" value="<?= @$user->last_name ?>" class="form-control" id="last_name" name="last_name" placeholder="Enter last name" required>
            </div>

        </div>
        </div>

        <div class="panel panel-default">
        <div class="panel-body">

            <div class="form-group">
                <label for="last_name">Password:</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password">
            </div>

            <div class="form-group">
                <label for="last_name">Confirm password:</label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Confirm new password">
            </div>


        </div>
        </div>

      <div class="form-group">
      <p class="form-control-static"><strong>Updated at</strong> <?= @$user->updated_at ?></p>
      </div>

      <div class="form-group">
        <label for="groups">Groups</label>
        <?php foreach ($groups as $group): ?>
          <div class="checkbox">
            <label><input name="groups[]" value="<?= $group->id ?>"type="checkbox" <?= @$user != null && $user->inGroup($group) ? 'checked' : '' ?> > <?= $group->name ?></label>
          </div>
        <?php endforeach; ?>

      </div>

      <button type="submit" class="btn btn-primary">Save</button>

    </form>

</div><!-- tab user -->
</div>

</div>