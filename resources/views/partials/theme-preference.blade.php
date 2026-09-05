<script>
    (() => {
        const QUERY = '(prefers-color-scheme: dark)';

        // The key the current page themes itself with. Admin and public keep
        // separate preferences, so the listener below has to know which one it is
        // looking at; applyDarkMode() records it.
        let activeKey = 'darkMode';

        // localStorage throws outright in some privacy modes. A theme is not worth
        // taking the page down for, so a failed read simply means "no choice yet"
        // and the system preference decides.
        const stored = (key) => {
            try {
                return localStorage.getItem(key);
            } catch (e) {
                return null;
            }
        };

        window.systemPrefersDark = () => window.matchMedia(QUERY).matches;

        /** true when the viewer has picked a theme themselves. */
        window.hasThemePreference = (storageKey = activeKey) => stored(storageKey) !== null;

        window.resolveDarkMode = (storageKey = 'darkMode') => {
            const savedTheme = stored(storageKey);

            return savedTheme === 'true'
                || (savedTheme === null && window.systemPrefersDark());
        };

        window.applyDarkMode = (storageKey = 'darkMode') => {
            activeKey = storageKey;

            const darkMode = window.resolveDarkMode(storageKey);
            document.documentElement.classList.toggle('dark', darkMode);

            return darkMode;
        };

        /** Forget the explicit choice and go back to following the system. */
        window.clearThemePreference = (storageKey = activeKey) => {
            try {
                localStorage.removeItem(storageKey);
            } catch (e) {
                // Nothing was stored to begin with.
            }

            const darkMode = window.applyDarkMode(storageKey);
            window.dispatchEvent(new CustomEvent('theme-changed', { detail: { dark: darkMode } }));

            return darkMode;
        };

        // Follow the system live. Without this the page only reads the preference
        // once at load, so switching the OS between light and dark left an open tab
        // on the old theme until it was reloaded.
        //
        // An explicit choice still wins: someone who picked light keeps light when
        // their machine switches to dark at sunset.
        window.matchMedia(QUERY).addEventListener('change', () => {
            if (window.hasThemePreference(activeKey)) {
                return;
            }

            const darkMode = window.applyDarkMode(activeKey);

            // Alpine holds the theme in reactive state, so flipping the class alone
            // would leave every :class binding on the old value.
            window.dispatchEvent(new CustomEvent('theme-changed', { detail: { dark: darkMode } }));
        });
    })();
</script>
