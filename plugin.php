<?php

class Todos extends Plugin
{
  private $dbFile = 'todos.json';

  // Titel der Adminseite setzen
  public function adminController()
  {
    global $layout;

    $layout['title'] = 'Todos | Bludit';

    // POST Requests verarbeiten
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

      // WICHTIG:
      // Der CSRF-Token wird vom Bludit-Core geprueft.
      // Wir muessen hier nur die Daten verarbeiten.

      $data = $this->loadData();

      // Sicherheitscheck: einfache action Variable
      $action = isset($_POST['action']) ? $_POST['action'] : '';

      switch ($action) {
        case 'addList':
          $this->handleAddList($data);
          break;
        case 'deleteList':
          $this->handleDeleteList($data);
          break;
        case 'addItem':
          $this->handleAddItem($data);
          break;
        case 'deleteItem':
          $this->handleDeleteItem($data);
          break;
        case 'toggleItem':
          $this->handleToggleItem($data);
          break;
        case 'moveListUp':
          $this->handleMoveList($data, -1);
          break;
        case 'moveListDown':
          $this->handleMoveList($data, 1);
          break;
        case 'reorderLists':
          $this->handleReorderLists($data);
          break;
      }

      $this->saveData($data);
    }
  }

  // Reihenfolge der Listen anhand eines ID-Arrays neu setzen
  private function handleReorderLists(&$data)
  {
    // Erwartet: order[] in $_POST
    if (!isset($_POST['order']) || !is_array($_POST['order'])) {
      return;
    }

    $order = $_POST['order'];

    // Map von ID auf Liste aufbauen
    $listsById = array();
    foreach ($data['lists'] as $list) {
      $listsById[$list['id']] = $list;
    }

    $newLists = array();

    // Zuerst alle IDs in der neuen Reihenfolge uebernehmen
    foreach ($order as $id) {
      if (isset($listsById[$id])) {
        $newLists[] = $listsById[$id];
        unset($listsById[$id]);
      }
    }

    // Falls es aus irgendeinem Grund noch Listen gibt, die nicht im Order-Array waren,
    // haengen wir sie hinten an, damit nichts verloren geht.
    foreach ($listsById as $list) {
      $newLists[] = $list;
    }

    $data['lists'] = $newLists;
  }

  // Adminseite rendern
  public function adminView()
  {
    global $security;

    $data = $this->loadData();
    $lists = $data['lists'];

    ob_start();
?>

    <div class="row">
      <div class="col-md-12 todos-admin-compact">
        <h2 style="font-size:1.1rem;margin-bottom:0.5rem;">Todo lists</h2>

        <style>
          /* Kompakteres Layout fuer das gesamte Plugin */
          .todos-admin-compact .form-group {
            margin-bottom: 0.25rem;
          }

          .todos-admin-compact #list-title {
            padding: 0.2rem 0.4rem;
            height: 30px;
            font-size: 0.85rem;
          }

          .todos-admin-compact .btn {
            padding: 0.1rem 0.35rem;
            font-size: 0.8rem;
            line-height: 1.2;
          }

          .todos-admin-compact .btn i {
            font-size: 1rem !important;
          }

          .todos-admin-compact .card {
            margin-bottom: 0.5rem;
          }

          .todos-admin-compact .card-header {
            cursor: move;
            font-size: 0.9rem;
          }

          .todos-admin-compact .card-body {
            padding: 0.4rem 0.5rem;
          }

          .todos-admin-compact .btn-group {
            gap: 4px !important;
          }

          .todos-admin-compact .list-group {
            margin-bottom: 0.4rem;
          }

          .todos-admin-compact .list-group-item {
            padding: 0.25rem 0.4rem;
            font-size: 0.85rem;
          }

          .todos-admin-compact .list-group-item form {
            margin-bottom: 0;
          }

          .todos-admin-compact .card-body p {
            margin-bottom: 0.25rem;
            font-size: 0.85rem;
          }

          .todos-admin-compact .input-group>.form-control {
            height: 30px;
            padding: 0.2rem 0.4rem;
            font-size: 0.85rem;
          }

          .todos-admin-compact .input-group-append .btn {
            padding: 0.1rem 0.4rem;
          }

          /* Einfache Masonry-Darstellung */
          #todo-lists-container {
            column-count: 2;
            column-gap: 1.2rem;
          }

          .todo-list-column {
            break-inside: avoid;
            -webkit-column-break-inside: avoid;
            -moz-column-break-inside: avoid;
            margin-bottom: 1.0rem;
          }

          /* Mobil einspaltig */
          @media (max-width: 767.98px) {
            #todo-lists-container {
              column-count: 1;
            }
          }
        </style>

        <!-- Neue Liste anlegen -->
        <form method="post" class="mb-2">
          <input type="hidden" name="action" value="addList">
          <input type="hidden" name="tokenCSRF" value="<?php echo $security->getTokenCSRF(); ?>">
          <div class="form-group">
            <label for="list-title" style="margin-bottom:0.15rem;font-size:0.85rem;">List title</label>
            <input type="text" class="form-control" id="list-title" name="title" required>
          </div>
          <button type="submit" class="btn btn-primary">Add list</button>
        </form>

        <?php if (!empty($lists)): ?>
          <?php $listsCount = count($lists); ?>

          <div id="todo-lists-container">
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
                      <!-- Liste nach oben -->
                      <form method="post" style="margin:0;">
                        <input type="hidden" name="action" value="moveListUp">
                        <input type="hidden" name="tokenCSRF" value="<?php echo $security->getTokenCSRF(); ?>">
                        <input type="hidden" name="id" value="<?php echo $this->escape($list['id']); ?>">
                        <button type="submit"
                          class="btn btn-sm btn-secondary"
                          title="Move list up"
                          aria-label="Move list up"
                          <?php echo ($index === 0) ? 'disabled' : ''; ?>>
                          <i class="fa fa-arrow-up"></i>
                        </button>
                      </form>

                      <!-- Liste nach unten -->
                      <form method="post" style="margin:0;">
                        <input type="hidden" name="action" value="moveListDown">
                        <input type="hidden" name="tokenCSRF" value="<?php echo $security->getTokenCSRF(); ?>">
                        <input type="hidden" name="id" value="<?php echo $this->escape($list['id']); ?>">
                        <button type="submit"
                          class="btn btn-sm btn-secondary"
                          title="Move list down"
                          aria-label="Move list down"
                          <?php echo ($index === $listsCount - 1) ? 'disabled' : ''; ?>>
                          <i class="fa fa-arrow-down"></i>
                        </button>
                      </form>

                      <!-- Liste loeschen -->
                      <form method="post" style="margin:0;">
                        <input type="hidden" name="action" value="deleteList">
                        <input type="hidden" name="tokenCSRF" value="<?php echo $security->getTokenCSRF(); ?>">
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

                    <!-- Bestehende Items -->
                    <?php if (!empty($list['items'])): ?>
                      <ul class="list-group mb-2">
                        <?php foreach ($list['items'] as $item): ?>
                          <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                              <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="toggleItem">
                                <input type="hidden" name="tokenCSRF" value="<?php echo $security->getTokenCSRF(); ?>">
                                <input type="hidden" name="listId" value="<?php echo $this->escape($list['id']); ?>">
                                <input type="hidden" name="itemId" value="<?php echo $this->escape($item['id']); ?>">
                                <input type="checkbox" onchange="this.form.submit()" <?php echo $item['done'] ? 'checked' : ''; ?>>
                              </form>
                              <span style="<?php echo $item['done'] ? 'text-decoration: line-through;' : ''; ?>">
                                <?php echo $this->escape($item['text']); ?>
                              </span>
                            </div>
                            <form method="post" style="margin:0;">
                              <input type="hidden" name="action" value="deleteItem">
                              <input type="hidden" name="tokenCSRF" value="<?php echo $security->getTokenCSRF(); ?>">
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

                    <!-- Neues Item fuer diese Liste -->
                    <form method="post" class="mt-1">
                      <input type="hidden" name="action" value="addItem">
                      <input type="hidden" name="tokenCSRF" value="<?php echo $security->getTokenCSRF(); ?>">
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

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Container, der alle Listenspalten enthaelt
        var container = document.getElementById('todo-lists-container');
        if (!container) {
          return;
        }

        var dragged = null;

        // Drag nur ueber den Header (Handle) starten
        container.querySelectorAll('.todo-list-handle').forEach(function(handle) {
          handle.addEventListener('dragstart', function(e) {
            var col = this.closest('.todo-list-column');
            if (!col) {
              return;
            }
            dragged = col;
            e.dataTransfer.effectAllowed = 'move';
            col.classList.add('dragging');
          });

          handle.addEventListener('dragend', function() {
            var col = this.closest('.todo-list-column');
            if (col) {
              col.classList.remove('dragging');
            }
            dragged = null;
          });
        });

        // Dragover auf den Spalten (Zielposition bestimmen)
        container.querySelectorAll('.todo-list-column').forEach(function(col) {
          col.addEventListener('dragover', function(e) {
            // Standard verhindern, sonst wird drop nicht ausgeloest
            e.preventDefault();
            if (!dragged) {
              return;
            }

            var target = this;
            if (target === dragged) {
              return;
            }

            e.dataTransfer.dropEffect = 'move';

            var rect = target.getBoundingClientRect();
            var middle = rect.top + rect.height / 2;

            // Oben oder unten einfuegen, je nachdem, wo die Maus ist
            if (e.clientY < middle) {
              container.insertBefore(dragged, target);
            } else {
              container.insertBefore(dragged, target.nextSibling);
            }
          });
        });

        // Wenn der Drop abgeschlossen ist, Reihenfolge speichern
        container.addEventListener('drop', function(e) {
          e.preventDefault();
          saveOrder();
        });

        // Reihenfolge per Fetch an PHP schicken
        function saveOrder() {
          var order = [];
          container.querySelectorAll('.todo-list-column').forEach(function(col) {
            var id = col.getAttribute('data-list-id');
            if (id) {
              order.push(id);
            }
          });

          var formData = new FormData();
          formData.append('action', 'reorderLists');
          formData.append('tokenCSRF', '<?php echo $security->getTokenCSRF(); ?>');

          // Array der IDs uebergeben
          for (var i = 0; i < order.length; i++) {
            formData.append('order[]', order[i]);
          }

          fetch('', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
          }).then(function(response) {
            // Optional: Rueckmeldung verarbeiten
            // console.log('Order saved');
          }).catch(function(error) {
            console.error('Error saving order', error);
          });
        }
      });
    </script>

<?php
    return ob_get_clean();
  }

  // Hilfsfunktionen -----------------------------------------------------

  // Daten laden oder leeres Grundgeruest liefern
  private function loadData()
  {
    $file = $this->workspace() . $this->dbFile;

    // Wenn Datei nicht existiert: leeres Geruest mit lists
    if (!file_exists($file)) {
      return array(
        'lists' => array()
      );
    }

    $json = file_get_contents($file);
    $data = json_decode($json, true);

    if (!is_array($data)) {
      $data = array(
        'lists' => array()
      );
    }

    // Falls die Struktur aus Versehen nur ein Array ist (alte Migration),
    // korrigieren wir das automatisch zu 'lists' => array(...)
    if (isset($data[0]) && !isset($data['lists'])) {
      $data = array(
        'lists' => $data
      );
    }

    if (!isset($data['lists']) || !is_array($data['lists'])) {
      $data['lists'] = array();
    }

    return $data;
  }

  // Daten speichern
  private function saveData($data)
  {
    $file = $this->workspace() . $this->dbFile;

    // Workspace Verzeichnis sicherstellen
    if (!is_dir($this->workspace())) {
      mkdir($this->workspace(), 0755, true);
    }

    file_put_contents(
      $file,
      json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
  }

  // Liste anlegen
  private function handleAddList(&$data)
  {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    if ($title === '') {
      $title = 'Untitled list';
    }

    $data['lists'][] = array(
      'id' => $this->generateId('l_'),
      'title' => $title,
      'items' => array()
    );
  }

  // Liste loeschen
  private function handleDeleteList(&$data)
  {
    $id = isset($_POST['id']) ? $_POST['id'] : '';

    $data['lists'] = array_values(array_filter(
      $data['lists'],
      function ($list) use ($id) {
        return $list['id'] !== $id;
      }
    ));
  }

  // Liste verschieben (direction: -1 nach oben, 1 nach unten)
  private function handleMoveList(&$data, $direction)
  {
    $id = isset($_POST['id']) ? $_POST['id'] : '';

    if ($id === '' || ($direction !== -1 && $direction !== 1)) {
      return;
    }

    $index = null;
    $count = count($data['lists']);

    for ($i = 0; $i < $count; $i++) {
      if ($data['lists'][$i]['id'] === $id) {
        $index = $i;
        break;
      }
    }

    if ($index === null) {
      return;
    }

    $newIndex = $index + $direction;

    if ($newIndex < 0 || $newIndex >= $count) {
      return;
    }

    $tmp = $data['lists'][$index];
    $data['lists'][$index] = $data['lists'][$newIndex];
    $data['lists'][$newIndex] = $tmp;
  }

  // Item hinzufuegen
  private function handleAddItem(&$data)
  {
    $listId = isset($_POST['listId']) ? $_POST['listId'] : '';
    $text = isset($_POST['text']) ? trim($_POST['text']) : '';

    if ($text === '') {
      return;
    }

    foreach ($data['lists'] as &$list) {
      if ($list['id'] === $listId) {
        $list['items'][] = array(
          'id' => $this->generateId('i_'),
          'text' => $text,
          'done' => false
        );
        break;
      }
    }
  }

  // Item loeschen
  private function handleDeleteItem(&$data)
  {
    $listId = isset($_POST['listId']) ? $_POST['listId'] : '';
    $itemId = isset($_POST['itemId']) ? $_POST['itemId'] : '';

    foreach ($data['lists'] as &$list) {
      if ($list['id'] === $listId) {
        $list['items'] = array_values(array_filter(
          $list['items'],
          function ($item) use ($itemId) {
            return $item['id'] !== $itemId;
          }
        ));
        break;
      }
    }
  }

  // Item Haken toggeln
  private function handleToggleItem(&$data)
  {
    $listId = isset($_POST['listId']) ? $_POST['listId'] : '';
    $itemId = isset($_POST['itemId']) ? $_POST['itemId'] : '';

    foreach ($data['lists'] as &$list) {
      if ($list['id'] === $listId) {
        foreach ($list['items'] as &$item) {
          if ($item['id'] === $itemId) {
            $item['done'] = !$item['done'];
            break;
          }
        }
        break;
      }
    }
  }

  // Einfache ID Erzeugung
  private function generateId($prefix)
  {
    return $prefix . uniqid();
  }

  // Kleine Escape Hilfe fuer Ausgabe
  private function escape($string)
  {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
  }

  public function adminSidebar()
  {
    // Pluginname anhand des Klassennamens
    $pluginName = Text::lowercase(__CLASS__);

    // URL zur Admin-Ansicht
    $url = HTML_PATH_ADMIN_ROOT . 'plugin/' . $pluginName;

    // Link im linken Admin-Menue ausgeben
    $html  = '<a class="nav-link" href="' . $url . '">';
    $html .= 'Todos';
    $html .= '</a>';

    return $html;
  }
}
