// Initialize Todos admin interactions: drag-and-drop ordering and checkbox submit.
document.addEventListener('DOMContentLoaded', function () {
  // Container that holds all list columns
  var container = document.getElementById('todo-lists-container');
  if (!container) {
    return;
  }

  var csrfToken = container.getAttribute('data-csrf') || '';
  var dragged = null;

  container.querySelectorAll('.todos-toggle-checkbox').forEach(function (checkbox) {
    checkbox.addEventListener('change', function () {
      if (this.form) {
        this.form.submit();
      }
    });
  });

  // Start dragging only from the list header handle
  container.querySelectorAll('.todo-list-handle').forEach(function (handle) {
    handle.addEventListener('dragstart', function (e) {
      var col = this.closest('.todo-list-column');
      if (!col) {
        return;
      }
      dragged = col;
      e.dataTransfer.effectAllowed = 'move';
      col.classList.add('dragging');
    });

    handle.addEventListener('dragend', function () {
      var col = this.closest('.todo-list-column');
      if (col) {
        col.classList.remove('dragging');
      }
      dragged = null;
    });
  });

  // Dragover on columns: find the intended position
  container.querySelectorAll('.todo-list-column').forEach(function (col) {
    col.addEventListener('dragover', function (e) {
      // Prevent default to allow dropping
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

      // Insert before or after depending on cursor position
      if (e.clientY < middle) {
        container.insertBefore(dragged, target);
      } else {
        container.insertBefore(dragged, target.nextSibling);
      }
    });
  });

  // When the drop finishes, persist the order
  container.addEventListener('drop', function (e) {
    e.preventDefault();
    saveOrder();
  });

  // Persist the new order to PHP via fetch
  function saveOrder() {
    var order = [];
    container.querySelectorAll('.todo-list-column').forEach(function (col) {
      var id = col.getAttribute('data-list-id');
      if (id) {
        order.push(id);
      }
    });

    if (!csrfToken) {
      console.error('Missing CSRF token for Todos order update');
      return;
    }

    var formData = new FormData();
    formData.append('action', 'reorderLists');
    formData.append('tokenCSRF', csrfToken);

    // Send array of ids
    for (var i = 0; i < order.length; i++) {
      formData.append('order[]', order[i]);
    }

    fetch('', {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    }).catch(function (error) {
      console.error('Error saving order', error);
    });
  }
});
