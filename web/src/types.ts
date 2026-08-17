/**
 * One project, exactly as php/json.php serialises it. The PHP Project class is
 * the definitive list; keep this in step with it.
 */
export interface Project {
  year: string
  slug: string
  /** "<year>/<slug>", relative to the asset root. */
  path: string
  rank: number
  /** 'project' when the assets do not say otherwise. */
  type: string
  title: string
  link: string
  owner: string
  description: string
  roles: string[]
  technologies: string[]
  design: string
  programming: string
  production: string
  content: string
  /** Paths relative to the asset root — see assetUrl() in config.ts. */
  images: string[]
  files: string[]
  preview: string
  /**
   * Text assets with no property of their own, as name => contents. PHP has one
   * array type for both, so an empty one arrives as [] rather than {}.
   */
  other: Record<string, string> | []
}
