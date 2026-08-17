import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import PortfolioView from '@/views/PortfolioView.vue'
import CvView from '@/views/CvView.vue'
import ThisView from '@/views/ThisView.vue'

declare module 'vue-router' {
  interface RouteMeta {
    /** The page's title, colons and all. The header and the page both read it. */
    title: string
  }
}

/**
 * The pages, in the order the header lists them. A title lives here and nowhere
 * else — the nav, the heading and document.title are all this one string, which
 * is why they can differ in more than their last word.
 */
export const pages: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'portfolio',
    component: PortfolioView,
    meta: { title: 'pike::portfolio' },
  },
  {
    path: '/cv',
    name: 'cv',
    component: CvView,
    meta: { title: 'pike::cv' },
  },
  {
    path: '/this',
    name: 'this',
    component: ThisView,
    meta: { title: 'about::this' },
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [...pages, { path: '/:pathMatch(.*)*', redirect: '/' }],
  scrollBehavior: () => ({ top: 0 }),
})

router.afterEach((to) => {
  document.title = to.meta.title ?? 'pike::portfolio'
})

export default router
