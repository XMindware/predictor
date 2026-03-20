@php
    $credentialDefaults = $provider->exists
        ? $provider->credentials->map(fn ($credential) => [
            'id' => $credential->id,
            'key' => $credential->key,
            'value' => $credential->value,
            'is_secret' => $credential->is_secret,
        ])->all()
        : [];
    $configDefaults = $provider->exists
        ? $provider->configs->map(fn ($config) => [
            'id' => $config->id,
            'key' => $config->key,
            'value' => $config->value,
        ])->all()
        : [];
    $credentialRows = old('credentials', $credentialDefaults);
    $configRows = old('configs', $configDefaults);
    if ($credentialRows === []) {
        $credentialRows = [['id' => null, 'key' => '', 'value' => '', 'is_secret' => true]];
    }
    if ($configRows === []) {
        $configRows = [['id' => null, 'key' => '', 'value' => '']];
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <style>
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, sans-serif;
            background: #f5f7fb;
            color: #172033;
        }
        main {
            max-width: 1120px;
            margin: 0 auto;
            padding: 40px 24px 80px;
        }
        header, section {
            background: #fff;
            border: 1px solid #dbe2ef;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 18px 44px rgba(23, 32, 51, 0.06);
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }
        h1, h2 {
            margin-top: 0;
        }
        p {
            color: #5b667a;
        }
        form {
            margin: 0;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }
        label {
            display: block;
            margin-bottom: 16px;
            font-size: 14px;
            font-weight: 600;
        }
        input, textarea {
            width: 100%;
            box-sizing: border-box;
            margin-top: 8px;
            padding: 12px 14px;
            border: 1px solid #c6d0e1;
            border-radius: 10px;
            font: inherit;
        }
        textarea {
            min-height: 110px;
            resize: vertical;
        }
        .checkbox {
            display: flex;
            gap: 10px;
            align-items: center;
            margin: 8px 0 24px;
            font-weight: 600;
        }
        .checkbox input {
            width: auto;
            margin: 0;
        }
        .panel {
            margin-top: 24px;
        }
        .rows {
            display: grid;
            gap: 12px;
        }
        .row {
            display: grid;
            grid-template-columns: 1.2fr 1.8fr auto auto;
            gap: 12px;
            align-items: end;
            padding: 16px;
            border: 1px solid #dbe2ef;
            border-radius: 14px;
            background: #f8fafc;
        }
        .row.config {
            grid-template-columns: 1.2fr 2fr auto;
        }
        .row button {
            white-space: nowrap;
        }
        .actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .error {
            margin-bottom: 20px;
            padding: 14px;
            border-radius: 12px;
            background: #fee2e2;
            color: #991b1b;
        }
        .status {
            margin-bottom: 20px;
            padding: 14px;
            border-radius: 12px;
            background: #dcfce7;
            color: #166534;
        }
        a, button {
            border: 0;
            border-radius: 10px;
            padding: 12px 16px;
            background: #1d4ed8;
            color: #fff;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }
        .secondary {
            background: #e2e8f0;
            color: #172033;
        }
        .danger {
            background: #dc2626;
        }
        @media (max-width: 900px) {
            .grid, .row, .row.config, header {
                grid-template-columns: 1fr;
            }
            header {
                display: grid;
            }
        }
    </style>
</head>
<body>
<main>
    <header>
        <div>
            <h1>{{ $pageTitle }}</h1>
            <p>Manage provider metadata, credentials, and runtime configuration from the admin area.</p>
        </div>

        <div class="actions">
            <a href="{{ route('admin.providers.index') }}" class="secondary">Back to Registry</a>
            @if ($provider->exists)
                <form method="POST" action="{{ route('admin.providers.test', $provider) }}">
                    @csrf
                    <button type="submit">Test API</button>
                </form>
            @endif
            <a href="{{ route('dashboard') }}" class="secondary">Dashboard</a>
        </div>
    </header>

    <section>
        @if (session('status'))
            <div class="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="error">
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('error'))
            <div class="error">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ $formAction }}">
            @csrf
            @if ($formMethod !== 'POST')
                @method($formMethod)
            @endif

            <div class="grid">
                <label>
                    Name
                    <input type="text" name="name" value="{{ old('name', $provider->name) }}" required>
                </label>

                <label>
                    Slug
                    <input type="text" name="slug" value="{{ old('slug', $provider->slug) }}" required>
                </label>

                <label>
                    Service
                    <input type="text" name="service" value="{{ old('service', $provider->service) }}" placeholder="weather, flights, news" required>
                </label>

                <label>
                    Driver
                    <input type="text" name="driver" value="{{ old('driver', $provider->driver) }}" placeholder="rest" required>
                </label>
            </div>

            <input type="hidden" name="active" value="0">
            <label class="checkbox">
                <input type="checkbox" name="active" value="1" {{ old('active', $provider->active) ? 'checked' : '' }}>
                Provider is active
            </label>

            <label>
                Notes
                <textarea name="notes">{{ old('notes', $provider->notes) }}</textarea>
            </label>

            <section class="panel">
                <div class="actions" style="justify-content: space-between;">
                    <div>
                        <h2>Credentials</h2>
                        <p>Store provider-specific credential keys. Leave value blank when it will be injected later.</p>
                    </div>

                    <button type="button" id="add-credential">Add Credential</button>
                </div>

                <div class="rows" id="credential-rows">
                    @foreach ($credentialRows as $index => $credential)
                        <div class="row">
                            <input type="hidden" name="credentials[{{ $index }}][id]" value="{{ $credential['id'] ?? '' }}">

                            <label>
                                Key
                                <input type="text" name="credentials[{{ $index }}][key]" value="{{ $credential['key'] ?? '' }}" placeholder="api_key">
                            </label>

                            <label>
                                Value
                                <input type="text" name="credentials[{{ $index }}][value]" value="{{ $credential['value'] ?? '' }}" placeholder="secret">
                            </label>

                            <label class="checkbox">
                                <input type="hidden" name="credentials[{{ $index }}][is_secret]" value="0">
                                <input type="checkbox" name="credentials[{{ $index }}][is_secret]" value="1" {{ ! array_key_exists('is_secret', $credential) || $credential['is_secret'] ? 'checked' : '' }}>
                                Secret
                            </label>

                            <button type="button" class="danger remove-row">Remove</button>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="panel">
                <div class="actions" style="justify-content: space-between;">
                    <div>
                        <h2>Configs</h2>
                        <p>Store non-secret provider config entries as simple key/value pairs.</p>
                    </div>

                    <button type="button" id="add-config">Add Config</button>
                </div>

                <div class="rows" id="config-rows">
                    @foreach ($configRows as $index => $config)
                        <div class="row config">
                            <input type="hidden" name="configs[{{ $index }}][id]" value="{{ $config['id'] ?? '' }}">

                            <label>
                                Key
                                <input type="text" name="configs[{{ $index }}][key]" value="{{ $config['key'] ?? '' }}" placeholder="base_url">
                            </label>

                            <label>
                                Value
                                <input type="text" name="configs[{{ $index }}][value]" value="{{ $config['value'] ?? '' }}" placeholder="https://api.example.com">
                            </label>

                            <button type="button" class="danger remove-row">Remove</button>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="actions" style="margin-top: 24px;">
                <button type="submit">{{ $provider->exists ? 'Save Changes' : 'Create Provider' }}</button>
                <a href="{{ route('admin.providers.index') }}" class="secondary">Cancel</a>
            </div>
        </form>
    </section>
</main>

<template id="credential-template">
    <div class="row">
        <input type="hidden" data-name="id" value="">

        <label>
            Key
            <input type="text" data-name="key" placeholder="api_key">
        </label>

        <label>
            Value
            <input type="text" data-name="value" placeholder="secret">
        </label>

        <label class="checkbox">
            <input type="hidden" data-name="is_secret_hidden" value="0">
            <input type="checkbox" data-name="is_secret" value="1" checked>
            Secret
        </label>

        <button type="button" class="danger remove-row">Remove</button>
    </div>
</template>

<template id="config-template">
    <div class="row config">
        <input type="hidden" data-name="id" value="">

        <label>
            Key
            <input type="text" data-name="key" placeholder="base_url">
        </label>

        <label>
            Value
            <input type="text" data-name="value" placeholder="https://api.example.com">
        </label>

        <button type="button" class="danger remove-row">Remove</button>
    </div>
</template>

<script>
    const bindRemoveButtons = (root) => {
        root.querySelectorAll('.remove-row').forEach((button) => {
            button.onclick = () => {
                const rows = button.closest('.rows');
                const row = button.closest('.row');

                if (rows.children.length === 1) {
                    row.querySelectorAll('input').forEach((input) => {
                        if (input.type === 'checkbox') {
                            input.checked = true;
                            return;
                        }

                        input.value = '';
                    });

                    return;
                }

                row.remove();
            };
        });
    };

    const appendRow = (templateId, containerId, prefix) => {
        const container = document.getElementById(containerId);
        const fragment = document.getElementById(templateId).content.cloneNode(true);
        const index = container.children.length;

        fragment.querySelectorAll('[data-name]').forEach((element) => {
            const key = element.getAttribute('data-name');

            if (key === 'is_secret_hidden') {
                element.name = `${prefix}[${index}][is_secret]`;
                return;
            }

            element.name = `${prefix}[${index}][${key}]`;
        });

        container.appendChild(fragment);
        bindRemoveButtons(container);
    };

    bindRemoveButtons(document);

    document.getElementById('add-credential').addEventListener('click', () => {
        appendRow('credential-template', 'credential-rows', 'credentials');
    });

    document.getElementById('add-config').addEventListener('click', () => {
        appendRow('config-template', 'config-rows', 'configs');
    });
</script>
</body>
</html>
