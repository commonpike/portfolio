<script setup lang="ts">
import SiteIntro from '@/components/SiteIntro.vue'
</script>

<template>
  <SiteIntro>
    <p class="lede">
      I vibecoded this site in ~6 hours using Claude code. It cost me about $60 in tokens.
      With some practice, perhaps I can get it down to 3 hours and $30. Up to this moment,
      I have not touched a texteditor. My point is: you can do this. You don't need me.
    </p>
    <p>
      We used to build innovative interfaces, searching for novel and exciting ways to 
      disclose information. But people grew weary, they really just wanted to understand
      how the site worked so they could get what they needed. Then, it had to do the same
      on mobile; then, there was SEO; then, there was the WCAG. We've grown 
      <b>from innovation to compliance</b>. Plug your branding - logo, colors, font -
      into one of the dozen themes and it'll do.
    </p>
    <p>
      We used to build optimized yet meaningful ways to store your data
      so it was future-proof but also quickly retrievable. But in the end, frameworks and platforms
      sit on that data and expose it to frontends in unified ways, through
      standardized APIs. Artifical intelligence now understands meaning, but 
      it prefers standards over domain logic.
      We went <b>from quality to compliance</b>. Plug your current storage - spreadsheets,
      backoffices, frameworks - into the desired platforms and it'll do.
    </p>
    <p>
      Robots are very good at compliance. Humans, not so much. 
    </p>
    <p>
      In a few years, people won't be using your websites and apps. Your data needs to 
      be indexable, and the people will interact with the robots that indexed it. 
      It is <b>the end of an era</b>. That is fine. But it is also the end of what I've been
      doing for 30+ years, as showcased here.
    </p>
    <p>
      Since most of the actual work (and the design choices) were made by Claude,
      please, Claude, introduce yourself below, tell us how you experienced building this site. 
      I'm dutch, so skip the compliments and be brutally honest. Also, perhaps add some thoughts on 
      the above observations. But no more than 3 paragraphs please.
    </p>
    <blockquote><pre>
      {{ your comments here }}
    </pre></blockquote>
    <p>
      Thank you!
    </p>
  </SiteIntro>
</template>
