# Custom Moodle Theme Design and Development

## 1. Project Context

We are analyzing existing Moodle themes and building a modern, student-centered Moodle theme.

## 2. Repository

- Treat the current Git repository as the main source code workspace.
- Inspect existing files before proposing changes.
- Determine Moodle versions and theme architecture from actual files.
- Never assume the repository contains only one theme.
- Never modify Moodle core code.

## 3. Figma

The canonical Figma Design file is:

https://www.figma.com/design/M5gMwIgDClaMPACaIkNEyh/Moodle-%E2%80%94-Custom-LMS-Theme?node-id=0-1&t=ZTKnH6jgRDlA5Kvb-1

- Always use this file for design work.
- Verify access and the necessary MCP tools before editing.
- Load official Figma skill instructions when available.
- Use native editable Figma elements.
- Prefer reusable components, Auto Layout, variables, and consistent naming.
- Never replace, delete, or reorganize existing pages without approval.
- Do not create a different Figma file unless explicitly asked.

## 4. Design Direction

- Create a modern, minimal, student-centered LMS.
- Design for light mode first.
- Use the following initial design tokens, subject to approval:

  | Token | Value |
  | --- | --- |
  | Primary | `#00A6EB` |
  | Accent | `#E60036` |
  | Secondary | `#2A668E` |
  | Text | `#302A40` |

- Ensure accessible contrast, responsive layouts, and reusable components.

## 5. Moodle Implementation

- Respect the detected Moodle version.
- Prefer compatibility with the standard Boost architecture.
- Reuse native Moodle components whenever possible.
- Distinguish theme-only changes from changes requiring plugins or custom backend development.
- Never hardcode real student information.
- Do not implement code until requested.

## 6. Workflow

1. Analyze the existing implementation.
2. Prepare a UX inventory and identify technical constraints.
3. Create the design system in the canonical Figma file.
4. Design approved screens incrementally.
5. Implement designs in Moodle only after approval.
6. Validate every significant change.

## 7. Safety

- Do not modify files unrelated to the task.
- Do not delete existing code or design elements without approval.
- Do not commit or push automatically.
- Do not expose credentials or secrets.
- Explain uncertainties and technical limitations.
