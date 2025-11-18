<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-600 leading-tight">
            {{ __('Plan Weekly Meal') }}
        </h2>
    </x-slot>

    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">

                {{-- The Form now wraps the navigation and the plan table --}}
                <form method="POST" action="{{ route('mealplans.store') }}">
                    @csrf

                    {{-- Hidden input to hold the calculated week_start (always a Monday) --}}
                    <input type="hidden" id="week_start" name="week_start" required>

                    {{-- ✅ Week Navigation Controls --}}
                    <div class="flex justify-between items-center mb-4">
                        <h5 class="fw-semibold mb-0">Planning Week: <span id="weekRange" class="badge bg-primary"></span></h5>
                        <div class="flex space-x-2">
                            <button type="button" id="prevWeekBtn" class="btn btn-sm btn-light border">&larr; Prev Week</button>
                            <button type="button" id="todayWeekBtn" class="btn btn-sm btn-info text-white mx-2">Current Week</button>
                            <button type="button" id="nextWeekBtn" class="btn btn-sm btn-light border">Next Week &rarr;</button>
                        </div>
                    </div>
                    
                    <hr class="mb-4">
                    
                    {{-- ✅ Weekly plan table --}}
                    <div id="weeklyPlan" class="mt-4">
                        <h5 class="fw-semibold mb-3">Plan Meals (7 Days)</h5>
                        @if ($errors->has('meals'))
                            <div>{{ $errors->first('meals') }}</div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Day</th>
                                        <th>Breakfast</th>
                                        <th>Lunch</th>
                                        <th>Dinner</th>
                                        <th>Snack</th>
                                    </tr>
                                </thead>
                                <tbody id="mealRows">
                                    {{-- Rows will be generated here by JavaScript --}}
                                </tbody>
                            </table>
                        </div>

                        <button type="submit" class="btn btn-success mt-3">Save Weekly Plan</button>
                        <a href="{{ route('mealplans.index') }}" class="btn btn-secondary mt-3">
                            Cancel
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>

    {{-- ✅ JavaScript to manage dates and generate the 7-day plan --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const inventoryItems = @json($inventoryItems);

        const weekStartInput = document.getElementById('week_start');
        const weekRangeSpan = document.getElementById('weekRange');
        const tbody = document.getElementById('mealRows');
        const prevWeekBtn = document.getElementById('prevWeekBtn');
        const todayWeekBtn = document.getElementById('todayWeekBtn');
        const nextWeekBtn = document.getElementById('nextWeekBtn');
        const weeklyPlan = document.getElementById('weeklyPlan');

        prevWeekBtn.disabled = true;
        prevWeekBtn.classList.add('disabled', 'btn-secondary');
        prevWeekBtn.style.pointerEvents = 'none';
        prevWeekBtn.style.opacity = '0.5';

        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function getMonday(d) {
            const date = new Date(d.getFullYear(), d.getMonth(), d.getDate());
            const day = date.getDay();
            const diff = (day + 6) % 7;
            date.setDate(date.getDate() - diff);
            return date;
        }

        const itemOptions = inventoryItems
            .filter(item => item.status !== 'expired' && item.quantity > 0)
            .sort((a, b) => new Date(a.expiry_date) - new Date(b.expiry_date))
            .map(item => {
                const expiryLabel = item.expiry_date ? ` (Exp: ${new Date(item.expiry_date).toISOString().split('T')[0]})` : '';
                const unitLabel = item.unit ? ` ${item.unit}` : '';
                const available = item.quantity ?? 0;
                const total = item.original_quantity ?? 0;
                return `<option value="${item.id}" data-available="${available}" data-original="${total}">
                            ${item.name}${expiryLabel} - ${unitLabel} [Available: ${available} / Total: ${total}]
                        </option>`;
            }).join('');

        // GENERATE WEEKLY PLAN
        function generatePlan(startDate) {
            tbody.innerHTML = '';
            weekStartInput.value = formatDate(startDate);
            const endDate = new Date(startDate);
            endDate.setDate(startDate.getDate() + 6);
            weekRangeSpan.textContent = `${formatDate(startDate)} — ${formatDate(endDate)}`;

            const mealTypes = ['breakfast', 'lunch', 'dinner', 'snack'];
            const today = new Date();

            for (let i = 0; i < 7; i++) {
                const current = new Date(startDate);
                current.setDate(startDate.getDate() + i);
                const dayName = current.toLocaleDateString('en-US', { weekday: 'short' });
                const dateFormatted = formatDate(current);

                let row = `<tr>
                    <td><strong>${dayName}</strong><br><small>${dateFormatted}</small></td>`;

                mealTypes.forEach(type => {
                    const mealIndex = `${i}_${type}`;
                    const isPast = current < new Date(today.getFullYear(), today.getMonth(), today.getDate());

                    row += `<td class="p-1">
                        <input type="hidden" name="meals[${mealIndex}][date]" value="${dateFormatted}">
                        <input type="hidden" name="meals[${mealIndex}][meal_type]" value="${type}">
                        <input type="hidden" name="meals[${mealIndex}][recipe_name]" value="">
                        <input type="hidden" name="meals[${mealIndex}][custom_recipe_name]" value="">

                        <select name="meals[${mealIndex}][recipe_name]" 
                            class="form-select form-select-sm mb-2 text-xs recipe-select" 
                            data-meal-index="${mealIndex}" ${isPast ? 'disabled' : ''}>
                            <option value="">-- Select Recipe --</option>
                            @foreach ($recipes as $recipe)
                                <option value="{{ $recipe['name'] }}" data-ingredients='@json($recipe['ingredients'])'>
                                    {{ $recipe['name'] }}
                                </option>
                            @endforeach
                        </select>

                        <input type="text" 
                            name="meals[${mealIndex}][custom_recipe_name]" 
                            class="form-control form-control-sm mb-2 text-xs custom-recipe-input" 
                            placeholder="Custom Meal Plan (optional)" ${isPast ? 'disabled' : ''}>

                        <div class="ingredient-list" data-meal-index="${mealIndex}">
                            <button type="button" class="btn btn-sm btn-outline-primary add-ingredient mt-1" 
                                data-meal-index="${mealIndex}">
                                + Add Ingredient
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger reset-ingredients mt-1" 
                                data-meal-index="${mealIndex}">
                                Reset
                            </button>
                        </div>

                    </td>`;
                });

                row += `</tr>`;
                tbody.insertAdjacentHTML('beforeend', row);
            }
        }

        let currentWeekStart = getMonday(new Date());

        function navigateWeek(weeks) {
            let dateToAdjust = weekStartInput.value ? new Date(weekStartInput.value) : currentWeekStart;
            dateToAdjust.setDate(dateToAdjust.getDate() + (weeks * 7));
            currentWeekStart = getMonday(dateToAdjust);
            generatePlan(currentWeekStart);
        }

        nextWeekBtn.addEventListener('click', () => navigateWeek(1));
        todayWeekBtn.addEventListener('click', () => {
            currentWeekStart = getMonday(new Date());
            generatePlan(currentWeekStart);
        });

        generatePlan(currentWeekStart);

        // ADD / REMOVE / RESET INGREDIENTS
        weeklyPlan.addEventListener('click', function (e) {
            if (e.target.classList.contains('add-ingredient')) {
                const button = e.target;
                const ingredientListDiv = button.closest('.ingredient-list');
                const mealIndex = button.dataset.mealIndex;
                const currentCount = ingredientListDiv.querySelectorAll('.mb-2').length;
                const newIndex = currentCount;

                const newIngredientHtml = `
                    <div class="d-flex align-items-center gap-1 mb-2 mt-1 flex-nowrap w-auto">
                        <select name="meals[${mealIndex}][ingredients][${newIndex}][inventory_item_id]"
                            class="form-select form-select-sm text-xs flex-shrink-0"
                            style="width: 55%;">
                            <option value="">Select Ingredient</option>
                            ${itemOptions}
                        </select>

                        <input type="number"
                            name="meals[${mealIndex}][ingredients][${newIndex}][quantity_used]"
                            class="form-control form-control-sm text-xs text-center flex-shrink-0"
                            style="width: 30%;" placeholder="Qty" min="1">

                        <button type="button" class="btn btn-sm btn-danger remove-ingredient flex-shrink-0">X</button>
                    </div>
                `;
                button.insertAdjacentHTML('beforebegin', newIngredientHtml);
            }

            if (e.target.classList.contains('remove-ingredient')) {
                e.target.closest('.mb-2').remove();
            }

            if (e.target.classList.contains('reset-ingredients')) {
                const ingredientListDiv = e.target.closest('.ingredient-list');
                ingredientListDiv.querySelectorAll('.mb-2').forEach(el => el.remove());

                const td = e.target.closest('td');
                if (td) {
                    const recipeSelect = td.querySelector('.recipe-select');
                    if (recipeSelect) recipeSelect.value = '';
                    const customInput = td.querySelector('.custom-recipe-input');
                    if (customInput) customInput.value = '';
                }
            }
        });

        // LIMIT QUANTITY BASED ON STOCK
        weeklyPlan.addEventListener('change', function(e) {
            if (e.target.matches('select[name*="[inventory_item_id]"]')) {
                const select = e.target;
                const ingredientId = select.value;
                const qtyInput = select.closest('.mb-2').querySelector('input[name*="[quantity_used]"]');

                const inventoryItem = inventoryItems.find(i => i.id == ingredientId);
                const available = inventoryItem ? inventoryItem.quantity : 0;
                const total = inventoryItem ? inventoryItem.original_quantity : 0;

                qtyInput.setAttribute('max', available);
                qtyInput.setAttribute('min', 1);
                qtyInput.placeholder = available > 0 ? `Max: ${available} / Total: ${total}` : 'Out of stock';
                qtyInput.value = '';
            }
        });

        weeklyPlan.addEventListener('input', function (e) {
            if (e.target.matches('input[name*="[quantity_used]"]')) {
                const max = parseFloat(e.target.max);
                const val = parseFloat(e.target.value);
                if (max && val > max) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Quantity Limit Exceeded',
                        text: `Only ${max} available for this ingredient.`,
                        confirmButtonText: 'OK'
                    });
                    e.target.value = max;
                }
            }
        });

        // CUSTOM vs RECIPE ALERT
        weeklyPlan.addEventListener('input', async function (e) {
            if (e.target.classList.contains('custom-recipe-input')) {
                const input = e.target;
                const td = input.closest('td');
                const recipeSelect = td.querySelector('.recipe-select');

                if (recipeSelect && recipeSelect.value && input.value.trim().length > 0) {
                    const result = await Swal.fire({
                        title: "Replace Recipe?",
                        text: "You've already selected a recipe. Typing a custom meal will remove it. Continue?",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Yes, replace recipe",
                        cancelButtonText: "Cancel",
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33"
                    });

                    if (result.isConfirmed) {
                        recipeSelect.value = "";
                        const ingredientList = td.querySelector('.ingredient-list');
                        ingredientList.querySelectorAll('.mb-2').forEach(el => el.remove());
                    } else {
                        input.value = "";
                    }
                }
            }
        });

        weeklyPlan.addEventListener('change', async function (e) {
            if (e.target.classList.contains('recipe-select')) {
                const select = e.target;
                const td = select.closest('td');
                const customInput = td.querySelector('.custom-recipe-input');

                if (customInput && customInput.value.trim().length > 0 && select.value) {
                    const result = await Swal.fire({
                        title: "Replace Custom Meal?",
                        text: "You've entered a custom meal name. Selecting a recipe will remove it. Continue?",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Yes, replace custom meal",
                        cancelButtonText: "Cancel"
                    });

                    if (result.isConfirmed) {
                        customInput.value = "";
                    } else {
                        select.value = "";
                        return;
                    }
                }

                // AUTO-FILL INGREDIENTS
                // AUTO-FILL INGREDIENTS
                const mealIndex = select.dataset.mealIndex;
                const ingredientList = td.querySelector('.ingredient-list');
                ingredientList.querySelectorAll('.mb-2').forEach(el => el.remove());

                const selectedOption = select.selectedOptions[0];
                const ingredientsData = selectedOption.dataset.ingredients ? JSON.parse(selectedOption.dataset.ingredients) : [];
                let ingredientCounter = 0;

                // Check first if all ingredients have enough stock
                let insufficient = false;

                for (const ingredient of ingredientsData) {
                    const ingredientName = ingredient.name.toLowerCase();
                    const requiredQty = ingredient.quantity_used ?? 1;

                    const availableItems = inventoryItems
                        .filter(i => i.name.toLowerCase() === ingredientName && i.status !== 'expired' && i.quantity > 0)
                        .sort((a, b) => new Date(a.expiry_date) - new Date(b.expiry_date));

                    let totalAvailable = availableItems.reduce((sum, i) => sum + i.quantity, 0);

                    if (totalAvailable < requiredQty) {
                        insufficient = true;
                        Swal.fire({
                            icon: 'warning',
                            title: 'Insufficient Ingredients',
                            text: `Not enough ${ingredient.name} available for this recipe.`
                        });
                        break; // stop checking further
                    }
                }

                // If any ingredient is insufficient, do not fill
                if (insufficient) {
                    select.value = '';
                    return;
                }

                // Otherwise, fill ingredients normally
                for (const ingredient of ingredientsData) {
                    const ingredientName = ingredient.name.toLowerCase();
                    const requiredQty = ingredient.quantity_used ?? 1;

                    const availableItems = inventoryItems
                        .filter(i => i.name.toLowerCase() === ingredientName && i.status !== 'expired' && i.quantity > 0)
                        .sort((a, b) => new Date(a.expiry_date) - new Date(b.expiry_date));

                    let remaining = requiredQty;
                    let usedItems = [];

                    for (const item of availableItems) {
                        if (remaining <= 0) break;
                        const take = Math.min(item.quantity, remaining);
                        if (take <= 0) continue;
                        usedItems.push({ id: item.id, quantity: take, available: item.quantity });
                        remaining -= take;
                    }

                    // Add usedItems to DOM
                    usedItems.forEach(used => {
                        const row = `
                            <div class="mb-2 d-flex align-items-center mt-1">
                                <select name="meals[${mealIndex}][ingredients][${ingredientCounter}][inventory_item_id]" 
                                    class="form-select form-select-sm me-1 text-xs" style="width:60%">
                                    <option value="">Select Ingredient</option>
                                    ${inventoryItems
                                        .filter(i => i.status !== 'expired')
                                        .map(i => `<option value="${i.id}" ${i.id == used.id ? 'selected' : ''}>${i.name}</option>`)
                                        .join('')
                                    }
                                </select>
                                <input type="number"
                                    name="meals[${mealIndex}][ingredients][${ingredientCounter}][quantity_used]"
                                    class="form-control form-control-sm text-xs text-center"
                                    style="width:40%"
                                    value="${used.quantity}"
                                    min="1"
                                    max="${used.available}"
                                    placeholder="Max: ${used.available}">
                            </div>
                        `;
                        ingredientList.insertAdjacentHTML('beforeend', row);
                        ingredientCounter++;
                    });
                }

            }
        });
    });
    </script>


</x-app-layout>