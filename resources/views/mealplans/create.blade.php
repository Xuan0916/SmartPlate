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
                    </div>
                </form>

            </div>
        </div>
    </div>

    {{-- ✅ JavaScript to manage dates and generate the 7-day plan --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const inventoryItems = @json($inventoryItems);
        const weekStartInput = document.getElementById('week_start');
        const weekRangeSpan = document.getElementById('weekRange');
        const tbody = document.getElementById('mealRows');
        
        const prevWeekBtn = document.getElementById('prevWeekBtn');
        const todayWeekBtn = document.getElementById('todayWeekBtn');
        const nextWeekBtn = document.getElementById('nextWeekBtn');

        // --- DATE UTILITY FUNCTIONS ---
        function formatDate(date) {
            return date.toISOString().split('T')[0];
        }

        function getMonday(date) {
            const day = date.getDay(); 
            const diff = date.getDate() - day + (day === 0 ? -6 : 1); 
            return new Date(date.setDate(diff));
        }

        // --- GENERATE ITEM OPTIONS ---
        const itemOptions = inventoryItems.map(item => 
            `<option value="${item.id}" data-available="${item.quantity}">${item.name}</option>`
        ).join('');

        // --- GENERATE WEEK PLAN TABLE ---
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
                        <td>
                            <input type="hidden" name="meals[${mealIndex}][date]" value="${dateFormatted}">
                            <input type="hidden" name="meals[${mealIndex}][meal_type]" value="${type}">
                            <input type="text" name="meals[${mealIndex}][recipe_name]" 
                                class="form-control form-control-sm mb-2 text-xs" 
                                placeholder="Meal name (e.g., Fried Rice)">

                            <div class="ingredient-list">
                                <div class="mb-2 ingredient-row-0 d-flex align-items-center mt-1">
                                    <select name="meals[${mealIndex}][ingredients][0][inventory_item_id]" 
                                        class="form-select form-select-sm me-1 text-xs"style="padding-right: 0.25rem;width:60%">
                                        <option value="">Select Ingredient</option>
                                        ${itemOptions}
                                    </select>
                                    <input type="number" name="meals[${mealIndex}][ingredients][0][quantity_used]" 
                                        class="form-control form-control-sm me-1 text-xs" 
                                        style="width: 40%;" placeholder="Qty">
                                </div>
                                <button type="button" 
                                    class="btn btn-sm btn-outline-primary add-ingredient mt-1" 
                                    data-meal-index="${mealIndex}">+ Add Another</button>
                            </div>
                        </td>`;
                });

                row += `</tr>`;
                tbody.insertAdjacentHTML('beforeend', row);
            }
        }

        // --- WEEK NAVIGATION ---
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

        // --- INITIAL RENDER ---
        generatePlan(currentWeekStart);

        // --- ADD/REMOVE INGREDIENT ROWS ---
        document.getElementById('weeklyPlan').addEventListener('click', function(e) {
            if (e.target.classList.contains('add-ingredient')) {
                const button = e.target;
                const ingredientListDiv = button.closest('.ingredient-list');
                const mealIndex = button.dataset.mealIndex;
                const currentCount = ingredientListDiv.querySelectorAll('.mb-2').length;
                const newIndex = currentCount;

                const newIngredientHtml = `
                    <div class="mb-2 ingredient-row-${newIndex} d-flex align-items-center mt-1">
                        <select name="meals[${mealIndex}][ingredients][${newIndex}][inventory_item_id]" 
                            class="form-select form-select-sm me-1 text-xs"style="padding-right: 0.25rem;width:60%">
                            <option value="">Select Ingredient</option>
                            ${itemOptions}
                        </select>
                        <input type="number" name="meals[${mealIndex}][ingredients][${newIndex}][quantity_used]" 
                            class="form-control form-control-sm me-1 text-xs" 
                            style="width: 40%;" placeholder="Qty">
                        <button type="button" class="btn btn-sm btn-danger remove-ingredient">X</button>
                    </div>
                `;
                button.insertAdjacentHTML('beforebegin', newIngredientHtml);
            }

            if (e.target.classList.contains('remove-ingredient')) {
                e.target.closest('.mb-2').remove();
            }
        });

        // --- LIMIT QUANTITY BASED ON AVAILABLE STOCK ---
        const weeklyPlan = document.getElementById('weeklyPlan');

        weeklyPlan.addEventListener('change', function(e) {
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

        weeklyPlan.addEventListener('input', function(e) {
            if (e.target.matches('input[name*="[quantity_used]"]')) {
                const max = parseFloat(e.target.max);
                const val = parseFloat(e.target.value);
                if (max && val > max) {
                    alert(`⚠️ Only ${max} available for this ingredient.`);
                    e.target.value = max;
                }
            }
        });
    });
    </script>

</x-app-layout>