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

        // ============================
        // DATE UTILITIES
        // ============================
        function formatDate(date) {
            return date.toISOString().split('T')[0];
        }

        function getMonday(date) {
            const day = date.getDay();
            const diff = date.getDate() - day + (day === 0 ? -6 : 1);
            return new Date(date.setDate(diff));
        }

        // ============================
        // ITEM OPTIONS (exclude expired)
        // ============================
        const itemOptions = inventoryItems
            .filter(item => item.status !== 'expired')
            .map(item =>
                `<option value="${item.id}" data-available="${item.quantity}">${item.name}</option>`
            ).join('');

        // ============================
        // GENERATE WEEKLY PLAN TABLE
        // ============================
        function generatePlan(startDate) {
            tbody.innerHTML = '';
            weekStartInput.value = formatDate(startDate);

            const endDate = new Date(startDate);
            endDate.setDate(startDate.getDate() + 6);
            weekRangeSpan.textContent = `${formatDate(startDate)} — ${formatDate(endDate)}`;

            const mealTypes = ['breakfast', 'lunch', 'dinner', 'snack'];

            for (let i = 0; i < 7; i++) {
                const current = new Date(startDate);
                current.setDate(startDate.getDate() + i);
                const dayName = current.toLocaleDateString('en-US', { weekday: 'short' });
                const dateFormatted = formatDate(current);

                let row = `<tr>
                    <td><strong>${dayName}</strong><br><small>${dateFormatted}</small></td>`;

                mealTypes.forEach(type => {
                    const mealIndex = `${i}_${type}`;
                    row += `
                        <td class="p-1">
                            <input type="hidden" name="meals[${mealIndex}][date]" value="${dateFormatted}">
                            <input type="hidden" name="meals[${mealIndex}][meal_type]" value="${type}">

                            <!-- Recipe Dropdown -->
                            <select name="meals[${mealIndex}][recipe_name]" 
                                class="form-select form-select-sm mb-2 text-xs recipe-select" 
                                data-meal-index="${mealIndex}">
                                <option value="">-- Select Recipe --</option>
                                @foreach ($recipes as $recipe)
                                    <option value="{{ $recipe['name'] }}" data-ingredients='@json($recipe['ingredients'])'>
                                        {{ $recipe['name'] }}
                                    </option>
                                @endforeach
                            </select>

                            <!-- Optional Custom Recipe -->
                            <input type="text" 
                                name="meals[${mealIndex}][custom_recipe_name]" 
                                class="form-control form-control-sm mb-2 text-xs custom-recipe-input" 
                                placeholder="Custom Meal Plan (optional)">

                            <div class="ingredient-list" data-meal-index="${mealIndex}">
                                <button type="button" class="btn btn-sm btn-outline-primary add-ingredient mt-1" data-meal-index="${mealIndex}">
                                    + Add Ingredient
                                </button>
                            </div>
                        </td>`;
                });

                row += `</tr>`;
                tbody.insertAdjacentHTML('beforeend', row);
            }
        }

        // ============================
        // WEEK NAVIGATION
        // ============================
        let currentWeekStart = getMonday(new Date());

        function navigateWeek(weeks) {
            let dateToAdjust = weekStartInput.value ? new Date(weekStartInput.value) : currentWeekStart;
            dateToAdjust.setDate(dateToAdjust.getDate() + (weeks * 7));
            currentWeekStart = getMonday(dateToAdjust);
            generatePlan(currentWeekStart);
        }

        prevWeekBtn.addEventListener('click', () => navigateWeek(-1));
        nextWeekBtn.addEventListener('click', () => navigateWeek(1));
        todayWeekBtn.addEventListener('click', () => {
            currentWeekStart = getMonday(new Date());
            generatePlan(currentWeekStart);
        });

        generatePlan(currentWeekStart);

        // ============================
        // ADD / REMOVE INGREDIENTS
        // ============================
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
                            style="width: 30%;" placeholder="Qty">

                        <button type="button" class="btn btn-sm btn-danger remove-ingredient flex-shrink-0">X</button>
                    </div>
                `;
                button.insertAdjacentHTML('beforebegin', newIngredientHtml);
            }

            if (e.target.classList.contains('remove-ingredient')) {
                e.target.closest('.mb-2').remove();
            }
        });

        // ============================
        // LIMIT QUANTITY BASED ON STOCK
        // ============================
        weeklyPlan.addEventListener('change', function (e) {
            if (e.target.matches('select[name*="[inventory_item_id]"]')) {
                const select = e.target;
                const available = select.selectedOptions[0]?.dataset.available || 0;
                const qtyInput = select.closest('.mb-2').querySelector('input[name*="[quantity_used]"]');
                qtyInput.setAttribute('max', available);
                qtyInput.setAttribute('min', 1);
                qtyInput.placeholder = available > 0 ? `Max: ${available}` : 'Out of stock';
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

        // ============================
        // SWEETALERT WARNINGS — CUSTOM vs RECIPE
        // ============================
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
                        Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Recipe cleared', showConfirmButton: false, timer: 1200 });
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
                        cancelButtonText: "Cancel",
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33"
                    });

                    if (result.isConfirmed) {
                        customInput.value = "";
                        Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Custom meal cleared', showConfirmButton: false, timer: 1200 });
                    } else {
                        select.value = "";
                    }
                }
            }
        });

        // ============================
        // AUTO-FILL INGREDIENTS FROM RECIPE (ALL OR NONE)
        // ============================
        weeklyPlan.addEventListener('change', function (e) {
            if (e.target.classList.contains('recipe-select')) {
                const select = e.target;
                const mealIndex = select.dataset.mealIndex;
                const ingredientList = select.closest('td').querySelector('.ingredient-list');
                ingredientList.querySelectorAll('.mb-2').forEach(el => el.remove());

                const selectedOption = select.selectedOptions[0];
                const ingredientsData = selectedOption.dataset.ingredients ? JSON.parse(selectedOption.dataset.ingredients) : [];

                // ✅ Check all ingredients first — if any are insufficient, cancel entirely
                const hasInsufficient = ingredientsData.some(ingredient => {
                    const match = inventoryItems.find(i =>
                        i.id == ingredient.inventory_item_id ||
                        (ingredient.name && i.name.toLowerCase() === ingredient.name.toLowerCase())
                    );
                    const used = ingredient.quantity_used ?? 1;
                    return !match || match.status === 'expired' || used > match.quantity;
                });

                if (hasInsufficient) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Insufficient Ingredients',
                        text: 'This recipe cannot be added because one or more ingredients are unavailable or insufficient.'
                    });
                    select.value = "";
                    return; // 🚫 Stop here — don’t add any ingredients
                }

                // ✅ If all are fine, add them all
                ingredientsData.forEach((ingredient, idx) => {
                    const matchingItem = inventoryItems.find(i =>
                        i.id == ingredient.inventory_item_id ||
                        (ingredient.name && i.name.toLowerCase() === ingredient.name.toLowerCase())
                    );

                    const itemId = matchingItem.id;
                    const available = matchingItem.quantity;
                    const used = ingredient.quantity_used ?? 1;

                    const row = `
                        <div class="mb-2 d-flex align-items-center mt-1">
                            <select name="meals[${mealIndex}][ingredients][${idx}][inventory_item_id]" 
                                class="form-select form-select-sm me-1 text-xs" style="width:60%">
                                <option value="">Select Ingredient</option>
                                ${inventoryItems.filter(i => i.status != 'expired').map(item =>
                                    `<option value="${item.id}" ${item.id == itemId ? 'selected' : ''}>${item.name}</option>`
                                ).join('')}
                            </select>
                            <input type="number"
                                name="meals[${mealIndex}][ingredients][${idx}][quantity_used]"
                                class="form-control form-control-sm text-xs text-center"
                                style="width:40%"
                                value="${used}"
                                min="1" max="${available}" placeholder="Max: ${available}">
                        </div>
                    `;
                    ingredientList.insertAdjacentHTML('beforeend', row);
                });
            }
        });
    });
    </script>

</x-app-layout>