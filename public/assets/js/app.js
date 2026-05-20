document.querySelectorAll('[data-confirm]').forEach((form) => {
  form.addEventListener('submit', (event) => {
    if (!confirm(form.dataset.confirm)) event.preventDefault();
  });
});

function readSubcategories(subcategorySelect) {
  const sourceId = subcategorySelect.dataset.subcategoriesSource;
  const sourceElement = sourceId ? document.getElementById(sourceId) : null;

  if (sourceElement) {
    try {
      const parsed = JSON.parse(sourceElement.textContent || '[]');
      if (Array.isArray(parsed) && parsed.length > 0) {
        return parsed.map((item) => ({
          id: String(item.id),
          nombre: String(item.nombre),
          categoria_id: String(item.categoria_id),
        }));
      }
    } catch (_error) {
      // Fallback to DOM options if JSON is not available/valid.
    }
  }

  return Array.from(subcategorySelect.options)
    .filter((option) => option.value !== '' && option.dataset.categoriaId)
    .map((option) => ({
      id: String(option.value),
      nombre: option.textContent || '',
      categoria_id: String(option.dataset.categoriaId),
    }));
}

function setupDependentSelect(scope) {
  const categorySelect = scope.querySelector('[data-category-select]');
  const subcategorySelect = scope.querySelector('[data-subcategory-select]');
  if (!categorySelect || !subcategorySelect) return;

  const allSubcategories = readSubcategories(subcategorySelect);
  const placeholder = subcategorySelect.dataset.placeholder || 'Subcategoría';

  const renderOptions = () => {
    const selectedCategoryId = String(categorySelect.value || '');
    const previousValue = String(subcategorySelect.value || '');

    const filtered = allSubcategories.filter((item) => {
      return selectedCategoryId === '' || item.categoria_id === selectedCategoryId;
    });

    subcategorySelect.innerHTML = '';

    const emptyOption = document.createElement('option');
    emptyOption.value = '';
    emptyOption.textContent = placeholder;
    subcategorySelect.appendChild(emptyOption);

    filtered.forEach((item) => {
      const option = document.createElement('option');
      option.value = item.id;
      option.textContent = item.nombre;
      option.dataset.categoriaId = item.categoria_id;
      subcategorySelect.appendChild(option);
    });

    const stillValid = filtered.some((item) => item.id === previousValue);
    subcategorySelect.value = stillValid ? previousValue : '';
  };

  categorySelect.addEventListener('change', renderOptions);
  renderOptions();

  subcategorySelect.addEventListener('change', () => {
    if (!subcategorySelect.value) return;
    const selected = allSubcategories.find((item) => item.id === String(subcategorySelect.value));
    if (selected && String(categorySelect.value || '') !== selected.categoria_id) {
      categorySelect.value = selected.categoria_id;
      renderOptions();
      subcategorySelect.value = selected.id;
    }
  });
}

document.querySelectorAll('[data-dependent-scope]').forEach((scope) => {
  setupDependentSelect(scope);
});
