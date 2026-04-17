document.addEventListener('DOMContentLoaded', function() {
  const textareas = document.querySelectorAll('.richTextBox');
  
  textareas.forEach((textarea) => {
    const htmlSource = document.createElement('textarea');
    htmlSource.classList.add('voyager-html-source');
    textarea.insertAdjacentElement('afterend', htmlSource);

    const tabContainer = document.createElement('div');
    const visualTab = document.createElement('button');
    const htmlTab = document.createElement('button');
    
    visualTab.innerText = 'Visuel';
    htmlTab.innerText = 'HTML';
    
    tabContainer.appendChild(visualTab);
    tabContainer.appendChild(htmlTab);
    textarea.insertAdjacentElement('afterend', tabContainer);

    const editor = tinymce.init({
      target: textarea,
      setup: function(ed) {
        ed.on('change', function() {
          if (visualTab.classList.contains('active')) {
            htmlSource.value = ed.getContent({ format: 'raw' });
          }
        });
        
        // Sync HTML to TinyMCE
        htmlSource.addEventListener('input', debounce(function() {
          ed.setContent(htmlSource.value, { format: 'raw' });
          ed.save();
        }, 300));
      }
    });

    visualTab.addEventListener('click', function() {
      textarea.style.display = '';
      htmlSource.style.display = 'none';
      visualTab.classList.add('active');
      htmlTab.classList.remove('active');
    });

    htmlTab.addEventListener('click', function() {
      textarea.style.display = 'none';
      htmlSource.style.display = '';
      htmlTab.classList.add('active');
      visualTab.classList.remove('active');
      htmlSource.value = editor.getContent({ format: 'raw' });
    });
    
    visualTab.click(); // Set default tab to Visual
  });

  function debounce(fn, delay) {
    let timer;
    return function(...args) {
      clearTimeout(timer);
      timer = setTimeout(() => fn.apply(this, args), delay);
    };
  }
});