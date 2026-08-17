<template>
  <section class="intro">
    <h1 class="title">pike<span class="colons">::</span>portfolio</h1>

    <p class="lede">
      Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut
      labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco
      laboris nisi ut aliquip ex ea commodo consequat.
    </p>

    <p class="muted">
      Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla
      pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt
      mollit anim id est laborum.
    </p>
  </section>
</template>

<style scoped>
.intro {
  padding-block: clamp(3rem, 10vh, 7rem) clamp(2rem, 6vh, 4rem);
}

.title {
  font-size: clamp(2.75rem, 11vw, 6.5rem);
  font-weight: 700;
  letter-spacing: -0.04em;
}

.colons {
  color: var(--p-primary-color);
}

.lede {
  max-width: 60ch;
  margin: clamp(1.5rem, 4vh, 2.5rem) 0 1.25rem;
  font-size: clamp(1.125rem, 1.6vw, 1.375rem);
  line-height: 1.5;
}

.muted {
  max-width: 60ch;
  margin: 0;
}
</style>
