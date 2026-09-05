<script>
    window.resolveDarkMode = (storageKey = 'darkMode') => {
        const savedTheme = localStorage.getItem(storageKey);

        return savedTheme === 'true'
            || (savedTheme === null && window.matchMedia('(prefers-color-scheme: dark)').matches);
    };

    window.applyDarkMode = (storageKey = 'darkMode') => {
        const darkMode = window.resolveDarkMode(storageKey);
        document.documentElement.classList.toggle('dark', darkMode);

        return darkMode;
    };
</script>