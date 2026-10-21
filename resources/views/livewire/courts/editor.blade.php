<div>
    <a href="{{ route('admin.courts.index') }}" class="court-back-link">← Volver a instalaciones</a>
    <section class="dashboard-intro">
        <div><p class="workspace-eyebrow">CADA DETALLE CUENTA</p><h1>{{ $courtId ? 'Editar pista' : 'Nueva pista' }}<span class="greeting-dot">.</span></h1><p>{{ $companyName ?? 'Selecciona la empresa propietaria' }} · Define el espacio, sus horas disponibles y sus tarifas.</p></div>
        <span class="workspace-badge badge-purple">Franjas de 60 minutos · EUR</span>
    </section>

    @if($errors->any())
        <div class="court-alert alert-error" role="alert"><div><strong>Revisa los datos antes de continuar.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
    @endif

    <form wire:submit="save" class="court-editor">
        <div class="court-editor-main">
            <section class="workspace-card court-form-section">
                <div class="court-section-heading"><span class="stat-icon icon-purple"><x-workspace-icon name="court" /></span><div><h2>Un espacio con identidad</h2><p>Los datos que ayudarán a reconocer tu pista.</p></div></div>
                <div class="court-form-grid">
                    @if(auth()->user()->hasRole('super-admin') && ! $courtId)
                        <x-court-field name="selected_company_id" label="Empresa propietaria" required class="field-wide">
                            <select id="selected_company_id" wire:model="selected_company_id" class="court-input" required><option value="">Selecciona una empresa</option>@foreach($companies as $company)<option value="{{ $company->id }}">{{ $company->name }}</option>@endforeach</select>
                        </x-court-field>
                    @endif
                    <x-court-field name="name" label="Nombre de la pista" required class="field-wide">
                        <input id="name" wire:model="name" type="text" class="court-input" maxlength="100" placeholder="Por ejemplo, Pista central" required />
                    </x-court-field>
                    @foreach(['sport' => ['Deporte', 'sports'], 'type' => ['Tipo de pista', 'types'], 'surface' => ['Superficie', 'surfaces'], 'enclosure' => ['Cerramiento', 'enclosures'], 'status' => ['Estado', 'statuses']] as $field => [$label, $options])
                        <x-court-field :name="$field" :label="$label" required>
                            <select id="{{ $field }}" wire:model="{{ $field }}" class="court-input" required>@foreach(config('courts.'.$options) as $key => $option)<option value="{{ $key }}">{{ $option }}</option>@endforeach</select>
                        </x-court-field>
                    @endforeach
                    <x-court-field name="capacity" label="Aforo / jugadores" required><input id="capacity" wire:model="capacity" type="number" min="1" max="100" step="1" class="court-input" required /></x-court-field>
                    <div class="court-feature-options field-wide">
                        <label><input type="checkbox" wire:model="lighting" /><x-workspace-icon name="sparkles" /><span>Iluminación</span></label>
                        <label><input type="checkbox" wire:model="accessible" /><x-workspace-icon name="check" /><span>Acceso adaptado</span></label>
                    </div>
                    <x-court-field name="description" label="Descripción y observaciones" class="field-wide" hint="Incluye información útil sobre el espacio o el equipamiento.">
                        <textarea id="description" wire:model="description" rows="3" maxlength="3000" class="court-input" placeholder="Lo que hace especial a esta pista…"></textarea>
                    </x-court-field>
                </div>
            </section>

            <section class="workspace-card court-form-section">
                <div class="court-section-heading"><span class="stat-icon icon-mint"><x-workspace-icon name="calendar" /></span><div><h2>Horas para jugar</h2><p>Marca las franjas alquilables de cada día. Las no marcadas permanecen cerradas.</p></div></div>
                <div class="court-week-tabs" role="group" aria-label="Día de la semana">
                    @foreach(config('courts.weekdays') as $day => $label)
                        <button type="button" wire:click="selectDay({{ $day }})" @class(['court-day-button', 'is-selected' => $selectedDay === $day]) aria-pressed="{{ $selectedDay === $day ? 'true' : 'false' }}">{{ mb_substr($label, 0, 3) }}<small>{{ count($weekly_hours[$day] ?? []) }} h</small></button>
                    @endforeach
                </div>
                <div class="court-hours-toolbar"><h3>{{ config('courts.weekdays.'.$selectedDay) }}</h3><div><button type="button" wire:click="setDayHours('all')">Marcar todas</button><button type="button" wire:click="setDayHours('none')">Limpiar día</button></div></div>
                <fieldset class="court-hour-grid" wire:key="weekday-{{ $selectedDay }}">
                    <legend class="sr-only">Franjas disponibles para {{ config('courts.weekdays.'.$selectedDay) }}</legend>
                    @foreach(range(0, 23) as $hour)
                        <label class="court-hour-option">
                            <input type="checkbox" wire:model.live="weekly_hours.{{ $selectedDay }}" value="{{ $hour }}" />
                            <span>{{ sprintf('%02d:00', $hour) }}<small>{{ sprintf('%02d:00', $hour + 1) }}</small><x-workspace-icon name="check" /></span>
                        </label>
                    @endforeach
                </fieldset>
                <div class="court-hours-footer"><p>La última franja (23:00–24:00) termina a medianoche. Una pista activa necesita alguna franja disponible.</p><button type="button" wire:click="copyDayToWeek" wire:confirm="¿Copiar el horario de este día a toda la semana? Se sustituirán los otros seis días." class="text-link">Copiar a toda la semana</button></div>
                @error('weekly_hours')<p class="field-error">{{ $message }}</p>@enderror
            </section>

            <section class="workspace-card court-form-section">
                <div class="court-section-heading"><span class="stat-icon icon-peach"><x-workspace-icon name="wallet" /></span><div><h2>Un precio para cada plan</h2><p>Tarifas finales en euros por hora, según las horas acumuladas de un cliente en esta pista y día.</p></div></div>
                <div class="court-price-grid">
                    @foreach(['hourly_price' => ['1 hora al día', 'Ej.: 20,00'], 'two_hour_price' => ['2 horas al día', 'Ej.: 18,00'], 'three_plus_hour_price' => ['3 o más horas al día', 'Ej.: 16,00']] as $field => [$label, $placeholder])
                        <x-court-field :name="$field" :label="$label" required>
                            <div class="court-price-input"><input id="{{ $field }}" wire:model.live.debounce.400ms="{{ $field }}" type="text" inputmode="decimal" maxlength="7" class="court-input" placeholder="{{ $placeholder }}" required /><span>€/h</span></div>
                        </x-court-field>
                    @endforeach
                </div>
                <div class="court-pricing-note"><x-workspace-icon name="clock" /><p><strong>Tarifa diaria retroactiva.</strong> Al sumar horas se recalcula todo el día, aunque las franjas no sean consecutivas. Si ya se ha cobrado una parte, se descuenta del nuevo total. Un ajuste negativo representa saldo a devolver.</p></div>
                <div class="court-calculator">
                    <h3>Prueba tus tarifas</h3><p>Simulación orientativa: las horas anteriores se valoran con las tarifas de este formulario. No consulta reservas ni genera cobros.</p>
                    <div class="court-calculator-inputs">
                        <x-court-field name="previewPreviousHours" label="Horas anteriores"><input id="previewPreviousHours" wire:model="previewPreviousHours" type="number" min="0" max="23" class="court-input" /></x-court-field>
                        <x-court-field name="previewNewHours" label="Horas a añadir"><input id="previewNewHours" wire:model="previewNewHours" type="number" min="1" max="24" class="court-input" /></x-court-field>
                        <button type="button" wire:click="previewPrice" wire:loading.attr="disabled" class="workspace-button court-secondary">Calcular</button>
                    </div>
                    @if($quote)
                        <div class="court-quote" role="status"><span>{{ $quote['hours'] }} h × {{ number_format($quote['hourly_rate_cents'] / 100, 2, ',', '.') }} €/h <strong>{{ number_format($quote['daily_total_cents'] / 100, 2, ',', '.') }} € total del día</strong></span><span>{{ $quote['adjustment_cents'] < 0 ? 'Saldo a devolver' : 'Pendiente por las horas añadidas' }}<strong>{{ number_format(abs($quote['adjustment_cents']) / 100, 2, ',', '.') }} €</strong></span></div>
                    @endif
                </div>
            </section>
        </div>

        <aside class="court-editor-side" aria-label="Imagen y ayuda">
            <section class="workspace-card court-form-section">
                <div class="court-section-heading"><div><h2>Una imagen, mil partidos</h2><p>Muestra el espacio tal y como es.</p></div></div>
                <div class="court-upload-preview">
                    @if($image && $imageReady)
                        <img src="{{ $image->temporaryUrl() }}" alt="Vista previa de la nueva imagen" />
                    @elseif($existingImageUrl && ! $remove_image)
                        <img src="{{ $existingImageUrl }}" alt="Imagen actual de la pista" />
                    @else
                        <div class="court-image-placeholder"><x-workspace-icon name="court" /><span>Tu pista merece una buena foto</span></div>
                    @endif
                </div>
                <x-court-field name="image" label="Imagen de la pista" hint="JPG, PNG o WebP. Máximo 5 MB y 6000 × 6000 px.">
                    <input id="image" type="file" wire:model="image" accept="image/jpeg,image/png,image/webp" class="court-file-input" />
                </x-court-field>
                <p class="field-hint" wire:loading wire:target="image" role="status">Subiendo imagen…</p>
                @if($existingImageUrl)<label class="court-remove-image"><input type="checkbox" wire:model.live="remove_image" />Quitar imagen actual al guardar</label>@endif
            </section>
            <div class="court-editor-help"><x-workspace-icon name="shield" /><h3>Solo en tu empresa</h3><p>Las pistas y su configuración pertenecen a la empresa asignada. Los clientes no pueden acceder al mantenimiento.</p></div>
            <div class="court-editor-help"><x-workspace-icon name="clock" /><h3>Preparada, no publicada</h3><p>Una pista inactiva o en mantenimiento no estará disponible para alquilar. Las reservas públicas se implementarán en el siguiente módulo.</p></div>
        </aside>
        <div class="court-save-bar">
            <p>Los campos con <span>*</span> son obligatorios. Todos los precios son finales en EUR.</p>
            <div class="court-inline-actions"><a href="{{ route('admin.courts.index') }}" class="workspace-button court-secondary">Cancelar</a><button type="submit" wire:loading.attr="disabled" wire:target="save,image" class="workspace-button court-primary"><span wire:loading.remove wire:target="save">{{ $courtId ? 'Guardar cambios' : 'Crear pista' }}</span><span wire:loading wire:target="save">Guardando…</span><x-workspace-icon name="check" /></button></div>
        </div>
    </form>
</div>
