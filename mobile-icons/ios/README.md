# iOS App Icon Assets (PhoneMonitor)

This folder contains the vector source for the PhoneMonitor app icon and instructions to generate the required PNG sizes for an iOS AppIcon asset catalog.

## Design
- Background: Brand green gradient (#22BB66 → #1A9950)
- Foreground: White phone with shield mark

## Export Options
Apple requires PNGs for app icons. Generate the sizes below using Xcode Asset Catalog or an export tool (e.g., Preview, Sketch, Figma, Affinity, Illustrator):

Required sizes (px):
- 20x20, 40x40, 60x60 (Notification)
- 29x29, 58x58, 87x87 (Settings/Spotlight)
- 40x40, 80x80, 120x120 (Spotlight)
- 60x60, 120x120, 180x180 (App Icon iPhone)
- 76x76, 152x152 (iPad)
- 83.5x83.5, 167x167 (iPad Pro)
- 1024x1024 (App Store)

## Quick generation in Xcode
1. In Xcode, open your iOS project → Assets.xcassets
2. Create a new App Icon set named `AppIcon`
3. Drag the generated PNGs into their slots (or use an icon generator by providing the 1024x1024 master)

## Source
The same SVG used for the web favicon is available at `../../assets/icons/favicon.svg`. Export to 1024x1024 PNG and downscale to the sizes above.
