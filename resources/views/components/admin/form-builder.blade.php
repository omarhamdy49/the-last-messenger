@props([
    'action' => '',
    'method' => 'POST',
    'enctype' => null,
    'class' => '',
])

@php
    $supportedLanguages = config('translatable.locales');
    if (is_callable($supportedLanguages)) {
        $supportedLanguages = $supportedLanguages();
    }

    $languageNames = [
        'ar' => 'العربية',
        'en' => 'English',
        'fr' => 'Français',
        'tr' => 'Türkçe',
        'ur' => 'اردو',
    ];
@endphp

<form action="{{ $action }}"
      method="{{ $method === 'GET' ? 'GET' : 'POST' }}"
      @if($enctype)
          enctype="{{ $enctype }}"
      @endif
      class="space-y-6 {{ $class }}"
      x-data="{
          currentLang: '{{ app()->getLocale() }}',
          errors: {},
          isDirty: false,
          isSubmitting: false,
          supportedLangs: {{ json_encode($supportedLanguages) }},
          langNames: {{ json_encode($languageNames) }},

          init() {
              // Load validation errors if any
              @if($errors->any())
                  this.errors = @json($errors->messages());
              @endif

              // Mark form as dirty on any input change
              this.$el.addEventListener('input', () => {
                  this.isDirty = true;
              });

              // Warn user about unsaved changes
              window.addEventListener('beforeunload', (e) => {
                  if (this.isDirty && !this.isSubmitting) {
                      e.preventDefault();
                      e.returnValue = '';
                  }
              });
          },

          setError(field, message) {
              this.errors[field] = [message];
          },

          clearError(field) {
              delete this.errors[field];
          },

          hasError(field) {
              return this.errors[field] !== undefined;
          },

          getError(field) {
              return this.errors[field] ? this.errors[field][0] : '';
          },

          validateField(field, rules) {
              // Basic client-side validation
              const value = this.$el.querySelector(`[name='${field}']`)?.value;

              if (rules.required && !value) {
                  this.setError(field, 'This field is required');
                  return false;
              }

              if (rules.email && value && !this.isValidEmail(value)) {
                  this.setError(field, 'Please enter a valid email address');
                  return false;
              }

              if (rules.url && value && !this.isValidUrl(value)) {
                  this.setError(field, 'Please enter a valid URL');
                  return false;
              }

              if (rules.min && value && value.length < rules.min) {
                  this.setError(field, `Minimum length is ${rules.min} characters`);
                  return false;
              }

              if (rules.max && value && value.length > rules.max) {
                  this.setError(field, `Maximum length is ${rules.max} characters`);
                  return false;
              }

              this.clearError(field);
              return true;
          },

          isValidEmail(email) {
              return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
          },

          isValidUrl(url) {
              try {
                  new URL(url);
                  return true;
              } catch {
                  return false;
              }
          },

          async handleSubmit(event) {
              event.preventDefault();

              // Validate all required fields
              const requiredFields = this.$el.querySelectorAll('[required]');
              let isValid = true;

              requiredFields.forEach(field => {
                  if (!field.value) {
                      this.setError(field.name, 'This field is required');
                      isValid = false;
                  }
              });

              if (!isValid) {
                  // Scroll to first error
                  const firstError = this.$el.querySelector('.error-message');
                  if (firstError) {
                      firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                  }
                  return;
              }

              this.isSubmitting = true;
              this.$el.submit();
          }
      }">

    @if($method !== 'GET')
        @csrf
    @endif

    @if(in_array($method, ['PUT', 'PATCH', 'DELETE']))
        @method($method)
    @endif

    {{ $slot }}

</form>
