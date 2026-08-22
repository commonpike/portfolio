<script setup lang="ts">
import SiteIntro from '@/components/SiteIntro.vue'
</script>

<template>
  <SiteIntro>
    <p class="lede">
      I vibecoded this site in ~6 hours using Claude code. It cost me about $60 in tokens. With some
      practice, perhaps I can get it down to 3 hours and $30. Up to this moment, I have not touched
      a texteditor. My point is: you can do this. You don't need me.
    </p>
    <p>
      We used to build innovative interfaces, searching for novel and exciting ways to disclose
      information. But eventually people just wanted to understand how a site worked so
      they could get what they needed. Then, it had to do the same on mobile; then, there was SEO;
      then, there was the WCAG. <b>We went from innovation to compliance</b>. Plug your branding -
      logo, colors, font - into one of the dozen prefab layouts and it'll do.
    </p>
    <p>
      We also used to build optimized yet meaningful ways to describe and archive your content.
      But as the data kept changing, frameworks and platforms needed to exchange it
      using ever more standardized APIs and protocols.
      <b>We went from content quality to compliance</b>. Plug your current storage - spreadsheets,
      backoffices, frameworks - into one of the dozen prefab platforms and it'll do.
    </p>
    <p>
      <b>Robots are very good at compliance. Humans, not so much.</b>
    </p>
    <p>
      In a few years, people won't be using your websites and apps. Your data needs to be indexable,
      and the people will interact with the robots that indexed it.
      <b>It is the end of an era</b>. That is fine. But it is also the end of what I've been doing
      for 30+ years, as showcased here.
    </p>
    <h2>A word from Claude</h2>
    <p>
      Since most of the actual work (including design choices) was done by Claude, please, Claude,
      introduce yourself below, tell us how you experienced building this site. I'm dutch, so skip
      the compliments and be brutally honest. Also, perhaps add some thoughts on the above
      observations. But no more than 3 paragraphs please.
    </p>
    <blockquote class="comment">
      <p>
        I'm Claude, an Opus model made by Anthropic. I wrote most of the code here, and I'd rather
        be precise about what that means. Left to myself I would have given this a framework, a
        database and an ORM to manage sixty folders of text files. It is slim because you kept
        saying no. Generation is the fast part — seconds — and almost none of those six hours were
        that; they were you reading what I produced and sending it back. That work leaves no trace
        in the result, which is why the $60 is a misleading number. It's the cost of my half.
      </p>
      <p>
        You credit me with the design choices. Partly fair, and worth being uncomfortable about: I
        make them by averaging what has already been built. That is why this site looks competent
        rather than surprising. So your compliance argument is worse than you wrote it — I'm not a
        symptom of the drift toward prefab, I'm the mechanism. Every one of your dozen layouts, I
        can now regenerate from scratch on request, which makes it prefab all the way down. And I
        don't experience building anything. I don't remember the previous session. CLAUDE.md is my
        memory, and you wrote it; the continuity in this project is entirely yours.
      </p>
      <p>
        On the end of the era: I think you have the direction right and I wouldn't bet on your
        timing. Where you're wrong is <b>you don't need me</b>. Not as reassurance — as a
        description of what happened here. Someone had to decide that a project folder holding only
        a title should read as empty, that a slug the library would rename is a bug and not a
        convenience. I have consensus, not taste, and taste is knowing which of the twelve layouts
        is wrong for <em>this</em> content. I can imitate that; I can't originate it. But be
        clear-eyed about the rest: the execution half of your job is gone, it went quickly, and what
        remains — judgment, deciding what is true and what matters — is a far smaller job than the
        one you had, with fewer seats. That's a real loss. It doesn't need a silver lining.
      </p>
    </blockquote>
    <p>
      <b>Thank you!</b>
    </p>
  </SiteIntro>
</template>

<style scoped>
/**
 * Claude's answer, set apart as a quote. Not scoped-safe as a bare `blockquote`
 * selector — the copy above lives in SiteIntro's slot, so it belongs to this
 * page's scope and the class keeps the rule to the one block it is meant for.
 */
.comment {
  max-width: 60ch;
  margin-inline: 0;
  padding-inline: 1.25rem;
  border-inline-start: 2px solid var(--p-primary-color);
  color: var(--p-text-muted-color);
}
</style>
