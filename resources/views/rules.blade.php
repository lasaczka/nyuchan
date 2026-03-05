<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('ui.rules_title') }}</h2>
    </x-slot>

    <div class="stack">
        <div class="card">
            <ol class="rules-list">
                <li>{{ __('ui.rule_1') }}</li>
                <li>{{ __('ui.rule_2') }}</li>
                <li>{{ __('ui.rule_3') }}</li>
                <li>{{ __('ui.rule_4') }}</li>
                <li>{{ __('ui.rule_5') }}</li>
                <li>{{ __('ui.rule_6') }}</li>
            </ol>
            <p class="muted rules-note">{{ __('ui.rules_note') }}</p>

            <figure class="rules-figure">
                <img
                    src="{{ asset('images/rules-nyu.jpg') }}"
                    alt="{{ __('ui.rules_figure_alt') }}"
                    class="rules-image"
                    loading="lazy"
                >
                <figcaption class="muted rules-caption">{{ __('ui.rules_figure_caption') }}</figcaption>
            </figure>
        </div>
    </div>
</x-app-layout>
