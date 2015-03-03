<div class="row">

  <div class="col-sm-12">

    <h3>Users</h3>

    <table class="table table-striped table-bordered table-hover">
      <tr>
        <th>User</th>
        <th>Member of</th>
        <th>Actions</th>
      </tr>
      <?php foreach ($users as $user): ?>
        <tr>
          <td>
              <strong><?= $user->first_name ?> <?= $user->last_name ?> 

              <?php if (\Sentry::findThrottlerByUserId($user->id)->isBanned() == 1): ?>
                <span class="label label-danger">Banned</span></strong>
              <?php elseif (\Sentry::findThrottlerByUserId($user->id)->isSuspended() == 1): ?>
                <span class="label label-warning">Suspended</span></strong>
              <?php elseif ($user->activated == 1): ?>
                <span class="label label-success">Active</span></strong>
              <?php else:  ?>
                <span class="label label-info">Inactive</span></strong>
              <?php endif; ?>

              <br /> <small><?= $user->email ?></small></h4>
          </td>
          <td>
            <?= implode(', ', array_map(function ($entry) {
                return $entry['name'];
              }, $user->groups()->find_array()));
            ?>
          </td>
          <td>
            <a href="/admin/users/<?= $user->id ?>" class="btn btn-primary" role="button"><span class="glyphicon glyphicon-pencil"></a>
            <form style="display: inline;" role="form" action="/admin/users/<?= @$user->id ?>" method="POST">
              <input type="hidden" name="_METHOD" value="DELETE"/>
              <button type="submit" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span></button>
            </form>
            <?php if (\Sentry::findThrottlerByUserId($user->id)->isSuspended() == 1): ?>
              <form style="display: inline;" role="form" action="/admin/users/<?= @$user->id ?>/throttle/unsuspend" method="POST">
                <button type="submit" class="btn btn-default">Unsuspend</span></button>
              </form>
            <?php else: ?>
              <form style="display: inline;" role="form" action="/admin/users/<?= @$user->id ?>/throttle/suspend" method="POST">
                <button type="submit" class="btn btn-default">Suspend</span></button>
              </form>  
            <?php endif; ?>

            <?php if (\Sentry::findThrottlerByUserId($user->id)->isBanned() == 1): ?>
              <form style="display: inline;" role="form" action="/admin/users/<?= @$user->id ?>/throttle/unban" method="POST">
                <button type="submit" class="btn btn-default">Unban</span></button>
              </form>
            <?php else: ?>
              <form style="display: inline;" role="form" action="/admin/users/<?= @$user->id ?>/throttle/ban" method="POST">
                <button type="submit" class="btn btn-default">Ban</span></button>
              </form>  
            <?php endif; ?>

          </td>
        </tr>
      <?php endforeach; ?>
      
    </table>
  
    <a href="/admin/users/createForm" class="btn btn-primary" role="button">Create user</a>

  </div>

</div>