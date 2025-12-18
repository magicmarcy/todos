<?php

/**
 * Todos plugin: manages todo lists within the Bludit admin.
 *
 * @license MIT
 * 
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class Todos extends Plugin
{
  /**
   * JSON file name used to persist todo data inside the workspace.
   *
   * @var string
   */
  private $dbFile = 'todos.json';

  /**
   * Handle incoming admin POST actions and set admin page metadata.
   *
   * @return void
   */
  public function adminController()
  {
    global $layout;

    $layout['title'] = 'Todos | Bludit';

    // Process incoming POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

      // IMPORTANT: CSRF token is validated by Bludit core; this block only processes the data.

      $data = $this->loadData();

      // Safety check: guard against missing action value
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

  /**
   * Reorder lists based on an array of list IDs received from the client.
   *
   * @param array $data Reference to stored todo data.
   * @return void
   */
  private function handleReorderLists(&$data)
  {
    // Expects order[] in $_POST
    if (!isset($_POST['order']) || !is_array($_POST['order'])) {
      return;
    }

    $order = $_POST['order'];

    // Build map of id to list for faster lookup
    $listsById = array();
    foreach ($data['lists'] as $list) {
      $listsById[$list['id']] = $list;
    }

    $newLists = array();

    // Add lists following the provided order first
    foreach ($order as $id) {
      if (isset($listsById[$id])) {
        $newLists[] = $listsById[$id];
        unset($listsById[$id]);
      }
    }

    // Append any remaining lists that were missing from the order array
    foreach ($listsById as $list) {
      $newLists[] = $list;
    }

    $data['lists'] = $newLists;
  }

  /**
   * Render the admin dashboard view.
   *
   * @return string
   */
  public function adminView()
  {
    global $security;

    $data = $this->loadData();
    $lists = $data['lists'];
    $csrfToken = $security->getTokenCSRF();

    ob_start();
    include __DIR__ . '/views/dashboard.php';

    return ob_get_clean();
  }

  // Helpers -----------------------------------------------------

  /**
   * Load todo data or return an empty structure when no file exists.
   *
   * @return array
   */
  private function loadData()
  {
    $file = $this->workspace() . $this->dbFile;

    // If file does not exist, return empty structure
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

    // If the structure is an array (legacy), wrap it into the lists key
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

  /**
   * Persist todo data to the workspace JSON file.
   *
   * @param array $data
   * @return void
   */
  private function saveData($data)
  {
    $file = $this->workspace() . $this->dbFile;

    // Ensure workspace directory exists
    if (!is_dir($this->workspace())) {
      mkdir($this->workspace(), 0755, true);
    }

    file_put_contents(
      $file,
      json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
  }

  /**
   * Add a new list to the stored data.
   *
   * @param array $data Reference to stored todo data.
   * @return void
   */
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

  /**
   * Delete a list by id.
   *
   * @param array $data Reference to stored todo data.
   * @return void
   */
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

  /**
   * Move a list up or down.
   *
   * @param array $data Reference to stored todo data.
   * @param int   $direction -1 to move up, 1 to move down.
   * @return void
   */
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

  /**
   * Add an item to a list.
   *
   * @param array $data Reference to stored todo data.
   * @return void
   */
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

  /**
   * Delete an item from a list.
   *
   * @param array $data Reference to stored todo data.
   * @return void
   */
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

  /**
   * Toggle the done state of an item.
   *
   * @param array $data Reference to stored todo data.
   * @return void
   */
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

  /**
   * Generate a simple unique id with prefix.
   *
   * @param string $prefix
   * @return string
   */
  private function generateId($prefix)
  {
    return $prefix . uniqid();
  }

  /**
   * Escape a string for safe HTML output.
   *
   * @param string $string
   * @return string
   */
  private function escape($string)
  {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
  }

  /**
   * Render sidebar link in the admin navigation.
   *
   * @return string
   */
  public function adminSidebar()
  {
    // Plugin name based on the class name
    $pluginName = Text::lowercase(__CLASS__);

    // URL to the plugin admin view
    $url = HTML_PATH_ADMIN_ROOT . 'plugin/' . $pluginName;

    // Output link in the left admin menu
    $html  = '<a class="nav-link" href="' . $url . '">';
    $html .= 'Todos';
    $html .= '</a>';

    return $html;
  }
}
