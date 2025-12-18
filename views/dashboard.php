<?php
/**
 * Dashboard view for the Todos plugin.
 *
 * @var array  $lists      Lists with items to render.
 * @var string $csrfToken  CSRF token injected by the controller.
 */

$listsCount = !empty($lists) ? count($lists) : 0;
?>

<link rel="stylesheet" href="<?php echo $this->domainPath() . 'css/todos.css'; ?>">
<script src="<?php echo $this->domainPath() . 'js/todos.js'; ?>" defer></script>

<div class="row">
  <div class="col-md-12 todos-admin-compact">
    <h2 class="todos-title">Todo lists</h2>

    <!-- Create new list -->
    <form method="post" class="mb-2">
      <input type="hidden" name="action" value="addList">
      <input type="hidden" name="tokenCSRF" value="<?php echo $this->escape($csrfToken); ?>">
      <div class="form-group">
        <label for="list-title" class="todos-label">List title</label>
        <input type="text" class="form-control" id="list-title" name="title" required>
      </div>
      <button type="submit" class="btn btn-primary">Add list</button>
    </form>

    <?php if (!empty($lists)): ?>
      <div id="todo-lists-container" data-csrf="<?php echo $this->escape($csrfToken); ?>">
        <?php foreach ($lists as $index => $list): ?>
          <div class="todo-list-column"
            draggable="false"
            data-list-id="<?php echo $this->escape($list['id']); ?>">

            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center font-weight-bold todo-list-handle"
                draggable="true"> <span>
                  <i class="fa fa-pencil-square-o"></i>
                  <?php echo $this->escape($list['title']); ?>
                </span>

                <div class="btn-group" role="group">
                  <!-- Move list up -->
                  <form method="post" class="todos-form-reset">
                    <input type="hidden" name="action" value="moveListUp">
                    <input type="hidden" name="tokenCSRF" value="<?php echo $this->escape($csrfToken); ?>">
                    <input type="hidden" name="id" value="<?php echo $this->escape($list['id']); ?>">
                    <button type="submit"
                      class="btn btn-sm btn-secondary"
                      title="Move list up"
                      aria-label="Move list up"
                      <?php echo ($index === 0) ? 'disabled' : ''; ?>>
                      <i class="fa fa-arrow-up"></i>
                    </button>
                  </form>

                  <!-- Move list down -->
                  <form method="post" class="todos-form-reset">
                    <input type="hidden" name="action" value="moveListDown">
                    <input type="hidden" name="tokenCSRF" value="<?php echo $this->escape($csrfToken); ?>">
                    <input type="hidden" name="id" value="<?php echo $this->escape($list['id']); ?>">
                    <button type="submit"
                      class="btn btn-sm btn-secondary"
                      title="Move list down"
                      aria-label="Move list down"
                      <?php echo ($index === $listsCount - 1) ? 'disabled' : ''; ?>>
                      <i class="fa fa-arrow-down"></i>
                    </button>
                  </form>

                  <!-- Delete list -->
                  <form method="post" class="todos-form-reset">
                    <input type="hidden" name="action" value="deleteList">
                    <input type="hidden" name="tokenCSRF" value="<?php echo $this->escape($csrfToken); ?>">
                    <input type="hidden" name="id" value="<?php echo $this->escape($list['id']); ?>">
                    <button type="submit"
                      class="btn btn-sm btn-danger"
                      title="Delete list"
                      aria-label="Delete list">
                      <i class="fa fa-trash"></i>
                    </button>
                  </form>
                </div>
              </div>
              <div class="card-body">

                <!-- Existing items -->
                <?php if (!empty($list['items'])): ?>
                  <ul class="list-group mb-2">
                    <?php foreach ($list['items'] as $item): ?>
                      <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                          <form method="post" class="todos-inline-form">
                            <input type="hidden" name="action" value="toggleItem">
                            <input type="hidden" name="tokenCSRF" value="<?php echo $this->escape($csrfToken); ?>">
                            <input type="hidden" name="listId" value="<?php echo $this->escape($list['id']); ?>">
                            <input type="hidden" name="itemId" value="<?php echo $this->escape($item['id']); ?>">
                            <input type="checkbox" class="todos-toggle-checkbox" <?php echo $item['done'] ? 'checked' : ''; ?>>
                          </form>
                          <span class="todo-item-text<?php echo $item['done'] ? ' todo-item-text-done' : ''; ?>">
                            <?php echo $this->escape($item['text']); ?>
                          </span>
                        </div>
                        <form method="post" class="todos-form-reset">
                          <input type="hidden" name="action" value="deleteItem">
                          <input type="hidden" name="tokenCSRF" value="<?php echo $this->escape($csrfToken); ?>">
                          <input type="hidden" name="listId" value="<?php echo $this->escape($list['id']); ?>">
                          <input type="hidden" name="itemId" value="<?php echo $this->escape($item['id']); ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="fa fa-trash"></i>
                          </button>
                        </form>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <p>No items yet.</p>
                <?php endif; ?>

                <!-- Add new item -->
                <form method="post" class="mt-1">
                  <input type="hidden" name="action" value="addItem">
                  <input type="hidden" name="tokenCSRF" value="<?php echo $this->escape($csrfToken); ?>">
                  <input type="hidden" name="listId" value="<?php echo $this->escape($list['id']); ?>">

                  <div class="input-group">
                    <input type="text" name="text" class="form-control" placeholder="New item..." required>
                    <div class="input-group-append">
                      <button type="submit" class="btn btn-secondary">
                        <i class="fa fa-plus"></i>
                      </button>
                    </div>
                  </div>
                </form>

              </div>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p>No todo lists yet.</p>
    <?php endif; ?>

  </div>
</div>
