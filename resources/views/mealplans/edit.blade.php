<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-600 leading-tight">
            {{ __('Edit Weekly Meal Plan') }}
        </h2>
    </x-slot>

    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">

                {{-- Form action points to the update method, using @method('PUT') --}}
                <form method="POST" action="{{ route('mealplans.update', $mealPlan) }}">
                    @csrf
                    @method('PUT')

                    {{-- Hidden input holds the original calculated week_start --}}
                    <input type="hidden" id="week_start" name="week_start" value="{{ $mealPlan->week_start }}" required>

                    {{-- Week Display --}}
                    @php
                        $startDate = \Carbon\Carbon::parse($mealPlan->week_start);
                        $endDate = $startDate->clone()->addDays(6);
                    @endphp
                    <div class="flex justify-between items-center mb-4">
                        <h5 class="fw-semibold mb-0">Editing Week: 
                            <span id="weekRange" class="badge bg-warning text-dark">
                                {{ $startDate->format('Y-m-d') }} &mdash; {{ $endDate->format('Y-m-d') }}
                            </span>
                        </h5>
                        <a href="{{ route('mealplans.index') }}" class="btn btn-sm btn-secondary">
                            &larr; Back to Index
                        </a>
                    </div>
                    
                    <hr class="mb-4">
                    
                    {{-- Weekly plan table (populated by JS) --}}
                    <div id="weeklyPlan" class="mt-4">
                        <h5 class="fw-semibold mb-3">Update Meals (7 Days)</h5>
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
                                    {{-- Rows will be populated here by JavaScript --}}
                                </tbody>
                            </table>
                        </div>

                        <button type="submit" class="btn btn-success mt-3">Save Changes</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    {{-- ✅ JavaScript to pre-populate the 7-day plan --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const inventoryItems = @json($inventoryItems);
        const mealPlanData = @json($mealPlan->meals);
        const startDateString = document.getElementById('week_start').value;

        const tbody = document.getElementById('mealRows');
        const mealTypes = ['breakfast', 'lunch', 'dinner', 'snack'];
        
        // Build lookup for existing meals
        const mealsLookup = mealPlanData.reduce((acc, meal) => {
            const key = `${meal.date}_${meal.meal_type}`;
            acc[key] = meal;
            return acc;
        }, {});

        function formatDate(date) {
            return date.toISOString().split('T')[0];
        }

        // --- Generate dropdown options (includes data-max-qty) ---
        function generateOptions(selectedId = null) {
            let options = '<option value="">Select Ingredient</option>';
            inventoryItems.forEach(item => {
                const selected = (item.id == selectedId) ? 'selected' : '';
                options += `<option value="${item.id}" ${selected} data-max-qty="${item.quantity}">${item.name}</option>`;
            });
            return options;
        }

        // --- Create ingredient row ---
        function createIngredientRowHtml(mealIndex, newIndex, itemId = null, qtyUsed = '') {
            const optionsHtml = generateOptions(itemId);
            let maxQty = 0;
            let placeholder = "Qty";

            if (itemId) {
                const item = inventoryItems.find(i => i.id == itemId);
                if (item) {
                    maxQty = item.quantity;
                    placeholder = maxQty > 0 ? `Max: ${maxQty}` : "Out of stock";
                }
            }

            return `
                <div class="mb-2 ingredient-row-${newIndex} flex items-center mt-1">
                    <select name="meals[${mealIndex}][ingredients][${newIndex}][inventory_item_id]" 
                        class="form-select form-select-sm me-1 text-xs ingredient-select" style="padding-right:0.25rem; width: 60%;">
                        ${optionsHtml}
                    </select>
                    <input type="number" name="meals[${mealIndex}][ingredients][${newIndex}][quantity_used]" 
                        class="form-control form-control-sm me-1 text-xs text-center qty-input" 
                        placeholder="${placeholder}" value="${qtyUsed}" 
                        min="1" max="${maxQty}" style="width: 40%;" ${maxQty === 0 ? 'disabled' : ''}>
                    <button type="button" class="btn btn-sm btn-danger remove-ingredient ms-1">X</button>
                </div>
            `;
        }

        // --- Generate 7-day table for editing ---
        function generateEditPlan(startDateString) {
            tbody.innerHTML = '';
            const startDate = new Date(startDateString);
            
            for (let i = 0; i < 7; i++) {
                const current = new Date(startDate);
                current.setDate(startDate.getDate() + i);
                const dayName = current.toLocaleDateString('en-US', { weekday: 'short' });
                const dateFormatted = formatDate(current);

                let row = `<tr>
                    <td class="p-2"><strong>${dayName}</strong><br><small>${dateFormatted}</small></td>`;

                mealTypes.forEach(type => {
                    const mealIndex = `${i}_${type}`;
                    const mealKey = `${dateFormatted}_${type}`;
                    const existingMeal = mealsLookup[mealKey];
                    
                    const recipeName = existingMeal ? existingMeal.recipe_name : '';
                    const existingIngredients = existingMeal && existingMeal.ingredients.length > 0 
                        ? existingMeal.ingredients : [{}];
                    
                    row += `
                        <td class="p-1">
                            <input type="hidden" name="meals[${mealIndex}][date]" value="${dateFormatted}">
                            <input type="hidden" name="meals[${mealIndex}][meal_type]" value="${type}">
                            
                            <input type="text" name="meals[${mealIndex}][recipe_name]" 
                                class="form-control form-control-sm mb-2 text-xs" 
                                placeholder="Meal name" value="${recipeName}">
                                
                            <div class="ingredient-list" data-meal-index="${mealIndex}">`;

                    existingIngredients.forEach((ingredient, ingIndex) => {
                        const itemId = ingredient.inventory_item_id ?? '';
                        const qtyUsed = ingredient.quantity_used ?? '';
                        row += createIngredientRowHtml(mealIndex, ingIndex, itemId, qtyUsed);
                    });

                    row += `
                        <button type="button" class="btn btn-sm btn-outline-primary add-ingredient mt-1" 
                            data-meal-index="${mealIndex}">+ Add Another</button>
                        </div>
                    </td>`;
                });

                row += `</tr>`;
                tbody.insertAdjacentHTML('beforeend', row);
            }
        }

        // --- Dynamic add/remove ---
        document.getElementById('weeklyPlan').addEventListener('click', function(e) {
            if (e.target.classList.contains('add-ingredient')) {
                const button = e.target;
                const ingredientListDiv = button.closest('.ingredient-list');
                const mealIndex = button.dataset.mealIndex;
                const newIndex = ingredientListDiv.querySelectorAll('.mb-2').length;
                const newIngredientHtml = createIngredientRowHtml(mealIndex, newIndex);
                button.insertAdjacentHTML('beforebegin', newIngredientHtml);
            }
            
            if (e.target.classList.contains('remove-ingredient')) {
                e.target.closest('.mb-2').remove(); 
            }
        });

        // --- Show max and validate quantities ---
        document.getElementById('weeklyPlan').addEventListener('change', function(e) {
            // When ingredient selected
            if (e.target.classList.contains('ingredient-select')) {
                const select = e.target;
                const maxQty = parseInt(select.selectedOptions[0]?.dataset.maxQty) || 0;
                const rowContainer = select.closest('.flex');
                const qtyInput = rowContainer.querySelector('.qty-input');

                if (qtyInput) {
                    qtyInput.max = maxQty;
                    qtyInput.min = 1;
                    qtyInput.placeholder = maxQty > 0 ? `Max: ${maxQty}` : 'Out of stock';
                    qtyInput.disabled = maxQty === 0;

                    if (qtyInput.value > maxQty) {
                        qtyInput.value = maxQty;
                        alert(`⚠️ You can only use up to ${maxQty} of this ingredient.`);
                    }
                }
            }

            // When quantity changes
            if (e.target.classList.contains('qty-input')) {
                const input = e.target;
                const max = parseFloat(input.max);
                const val = parseFloat(input.value);
                if (max && val > max) {
                    alert(`⚠️ Maximum available is ${max}. Adjusted automatically.`);
                    input.value = max;
                } else if (val < 1) {
                    input.value = 1;
                }
            }
        });

        // --- Initial render ---
        if (startDateString) {
            generateEditPlan(startDateString);
        }
    });
    </script>

</x-app-layout>