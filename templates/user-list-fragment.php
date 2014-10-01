<div class="row">

  <div class="col-sm-12">

    <h3>Users</h3>

    <table class="table table-striped table-bordered table-hover">
      <tr>
        <th>Actions</th>
        <th>Email</th>
        <th>Activated</th>
        <th>Suspended</th>
        <th>Banned</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Modified</th>
        <th>Member of</th>
      </tr>
      <?php foreach ($users as $user): ?>
        <tr>
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
          <td><?= $user->email ?></td>
          <td><?= ($user->activated == 1) ? 'Yes' : 'No' ?></td>
          <td>
            <?= (\Sentry::findThrottlerByUserId($user->id)->isSuspended() == 1) ? 'Yes' : 'No' ?>
          </td>
          <td>
            <?= (\Sentry::findThrottlerByUserId($user->id)->isBanned() == 1) ? 'Yes' : 'No' ?>
          </td>
          <td><?= $user->first_name ?></td>
          <td><?= $user->last_name ?></td>
          <td><?= $user->updated_at ?></td>
          <td>
            <?= implode(', ', array_map(function ($entry) {
                return $entry['name'];
              }, $user->groups->toArray()));
            ?>
          </td>
        </tr>
      <?php endforeach; ?>
      
    </table>

    <a href="/admin/users/createForm" class="btn btn-primary" role="button">Create user</a>

  </div>

</div>