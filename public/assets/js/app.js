document.querySelectorAll('[data-confirm]').forEach((form) => {
  form.addEventListener('submit', (event) => {
    if (!confirm(form.dataset.confirm)) event.preventDefault();
  });
});

function setupDependentSelect(scope) {
  const categorySelect = scope.querySelector('[data-category-select]');
  const subcategorySelect = scope.querySelector('[data-subcategory-select]');
  if (!categorySelect || !subcategorySelect) return;

  const sourceId = subcategorySelect.dataset.subcategoriesSource;
  const sourceElement = sourceId ? document.getElementById(sourceId) : null;
  const allSubcategories = sourceElement ? JSON.parse(sourceElement.textContent || '[]') : [];

  const placeholder = subcategorySelect.dataset.placeholder || 'Subcategoría';

  const renderOptions = () => {
    const selectedCategoryId = categorySelect.value;
    const previousValue = subcategorySelect.value;
    const filtered = allSubcategories.filter((item) => {
      return selectedCategoryId === '' || String(item.categoria_id) === String(selectedCategoryId);
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
      subcategorySelect.appendChild(option);
    });

    const stillValid = filtered.some((item) => String(item.id) === String(previousValue));
    subcategorySelect.value = stillValid ? previousValue : '';
  };

  categorySelect.addEventListener('change', renderOptions);
  renderOptions();

  subcategorySelect.addEventListener('change', () => {
    if (!subcategorySelect.value) return;
    const selected = allSubcategories.find((item) => String(item.id) === String(subcategorySelect.value));
    if (selected && String(categorySelect.value) !== String(selected.categoria_id)) {
      categorySelect.value = String(selected.categoria_id);
      renderOptions();
      subcategorySelect.value = String(selected.id);
    }
  });
}

document.querySelectorAll('[data-dependent-scope]').forEach((scope) => {
  setupDependentSelect(scope);
});
