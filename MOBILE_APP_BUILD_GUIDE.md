# 📱 PhoneMonitor Mobile App Build Guide

Complete guide for building PhoneMonitor apps for Android and iOS devices.

---

## 📋 Table of Contents

1. [Platform Compatibility](#platform-compatibility)
2. [Android Build Guide](#android-build-guide)
3. [iOS Build Guide](#ios-build-guide)
4. [Configuration](#configuration)
5. [Testing](#testing)
6. [Distribution](#distribution)
7. [Troubleshooting](#troubleshooting)

---

## 🔄 Platform Compatibility

### Current Status

✅ **Android:** Fully supported (Android 6.0+ / API 23+)  
⚠️ **iOS:** Requires native Swift/Objective-C development

### Android Support
- Minimum: Android 6.0 Marshmallow (API 23)
- Target: Android 14 (API 34)
- Architecture: ARM, ARM64, x86, x86_64
- Language: Kotlin
- Size: ~8-12 MB

### iOS Compatibility Notes

**Current Situation:**
- This repository contains only the Android app (Kotlin/Java)
- iOS requires a separate native app written in Swift or Objective-C
- The web API is fully compatible with iOS apps

**To Support iOS:**
You have 3 options:

1. **Native iOS App (Recommended)**
   - Create new Xcode project in Swift
   - Use same API endpoints (api/register.php, api/ping.php, etc.)
   - Implement Core Location for GPS tracking
   - See "iOS Development Guide" section below

2. **React Native (Cross-platform)**
   - Build one codebase for both Android and iOS
   - Requires complete app rewrite
   - Good for long-term maintenance

3. **Flutter (Cross-platform)**
   - Dart language, compiles to native code
   - Single codebase for both platforms
   - Excellent performance

---

## 🤖 Android Build Guide

### Prerequisites

**Required Software:**
- **Android Studio** (Latest version - Arctic Fox or newer)
  - Download: https://developer.android.com/studio
- **Java Development Kit (JDK)** 11 or higher
  - Included with Android Studio
- **Android SDK** (installed via Android Studio)
  - API Level 23 (Android 6.0) minimum
  - API Level 34 (Android 14) recommended

**System Requirements:**
- Windows 10/11, macOS 10.14+, or Linux
- 8 GB RAM minimum (16 GB recommended)
- 8 GB free disk space
- 1280x800 minimum screen resolution

### Step 1: Download & Setup

1. **Clone the repository:**
```bash
git clone https://github.com/irlam/PhoneMonitor.git
cd PhoneMonitor/android
```

2. **Open in Android Studio:**
   - Launch Android Studio
   - Click "Open" (not "New Project")
   - Navigate to `PhoneMonitor/android` folder
   - Click "OK"

3. **Wait for Gradle Sync:**
   - Android Studio will automatically download dependencies
   - This may take 5-15 minutes on first run
   - Watch the bottom status bar for progress

### Step 2: Configure the App

**Edit Configuration File:**

Open `android/app/src/main/res/values/strings.xml` and update:

```xml
<resources>
    <string name="app_name">PhoneMonitor</string>
    
    <!-- CHANGE THIS to your server URL -->
    <string name="api_base_url">https://phone-monitor.defecttracker.uk/api/</string>
    
    <string name="notification_channel_name">Location Tracking</string>
    <string name="notification_channel_desc">Keeps the app running in the background</string>
</resources>
```

**Or Edit Kotlin Constants:**

Open `android/app/src/main/java/com/phonemonitor/app/Constants.kt`:

```kotlin
object Constants {
    // CHANGE THIS to your server URL
    const val API_BASE_URL = "https://phone-monitor.defecttracker.uk/api/"
    
    const val LOCATION_UPDATE_INTERVAL = 15 * 60 * 1000L // 15 minutes
    const val LOCATION_FASTEST_INTERVAL = 5 * 60 * 1000L // 5 minutes
    
    const val NOTIFICATION_ID = 12345
    const val NOTIFICATION_CHANNEL_ID = "phone_monitor_location"
}
```

### Step 3: Update App Signing (Optional but Recommended)

**For Release Builds, Create a Keystore:**

```bash
# Navigate to android/app directory
cd android/app

# Generate keystore
keytool -genkey -v -keystore phonemonitor-release.keystore \
  -alias phonemonitor \
  -keyalg RSA \
  -keysize 2048 \
  -validity 10000

# You'll be prompted for:
# - Keystore password (remember this!)
# - Your name, organization, etc.
# - Key password (can be same as keystore password)
```

**Update build.gradle:**

Edit `android/app/build.gradle` and add:

```groovy
android {
    ...
    
    signingConfigs {
        release {
            storeFile file('phonemonitor-release.keystore')
            storePassword 'your_keystore_password'
            keyAlias 'phonemonitor'
            keyPassword 'your_key_password'
        }
    }
    
    buildTypes {
        release {
            signingConfig signingConfigs.release
            minifyEnabled true
            proguardFiles getDefaultProguardFile('proguard-android-optimize.txt'), 'proguard-rules.pro'
        }
    }
}
```

**Security Note:** Never commit keystore files or passwords to Git!

### Step 4: Build the App

**Option A: Debug Build (for testing)**

1. Connect Android device via USB (enable Developer Mode & USB Debugging)
2. Or use Android Emulator (Tools → AVD Manager → Create Virtual Device)
3. In Android Studio, click the green "Run" button (▶️)
4. Select your device
5. App will install and launch automatically

**Option B: Release APK (for distribution)**

Using Android Studio:
1. Menu: **Build** → **Generate Signed Bundle / APK**
2. Select **APK**
3. Click **Next**
4. Choose your keystore file (or create new)
5. Enter passwords
6. Click **Next**
7. Select **release** build variant
8. Check **V2 (Full APK Signature)**
9. Click **Finish**

APK will be saved to: `android/app/release/app-release.apk`

**Option C: Command Line Build**

```bash
# Debug APK
cd android
./gradlew assembleDebug
# Output: android/app/build/outputs/apk/debug/app-debug.apk

# Release APK (requires signing config)
./gradlew assembleRelease
# Output: android/app/build/outputs/apk/release/app-release.apk
```

### Step 5: Install on Device

**Method 1: Android Studio**
- Click Run button with device connected

**Method 2: ADB (Android Debug Bridge)**
```bash
adb install android/app/release/app-release.apk
```

**Method 3: Manual Installation**
- Transfer APK to device
- Open file manager on device
- Tap APK file
- Enable "Install from Unknown Sources" if prompted
- Tap "Install"

### App Size & Performance

- **Debug APK:** ~15-20 MB (includes debugging symbols)
- **Release APK:** ~8-12 MB (optimized & minified)
- **Battery Impact:** Low (location updates every 15 minutes)
- **Network Usage:** Minimal (~10 KB per update)
- **RAM Usage:** ~30-50 MB when running

---

## 🍎 iOS Build Guide

### Current Status

❌ **iOS app not yet implemented**

The current repository only contains the Android app. To support iOS devices, you need to create a native iOS app.

### Option 1: Build Native iOS App

**What You Need:**
- Mac computer (required for iOS development)
- Xcode 14+ (free from Mac App Store)
- Apple Developer Account ($99/year for App Store distribution)
- Basic Swift knowledge

**Architecture Overview:**

```
iOS App Components:
├── Location Services (Core Location)
├── Background Tasks (BGTaskScheduler)
├── Network API (URLSession or Alamofire)
├── Local Storage (UserDefaults/CoreData)
└── Persistent Notification (UNUserNotificationCenter)

API Endpoints (Already Built):
├── POST /api/register.php  - Register device
├── POST /api/ping.php      - Send location updates
├── POST /api/consent.php   - Update consent status
└── POST /api/revoke.php    - Revoke access
```

**Steps to Create iOS App:**

1. **Create Xcode Project:**
   - Open Xcode
   - File → New → Project
   - Choose "App" template
   - Language: Swift
   - Interface: SwiftUI or UIKit

2. **Add Capabilities:**
   - Target → Signing & Capabilities
   - Add "Background Modes"
     - ✓ Location updates
     - ✓ Background fetch
   - Add "Push Notifications" (optional)

3. **Configure Info.plist:**
```xml
<key>NSLocationAlwaysAndWhenInUseUsageDescription</key>
<string>PhoneMonitor needs your location to help family members track your device with your consent.</string>

<key>NSLocationWhenInUseUsageDescription</key>
<string>PhoneMonitor needs your location to provide tracking features.</string>

<key>UIBackgroundModes</key>
<array>
    <string>location</string>
    <string>fetch</string>
</array>
```

4. **Implement Location Manager:**
```swift
import CoreLocation

class LocationManager: NSObject, CLLocationManagerDelegate {
    let manager = CLLocationManager()
    
    override init() {
        super.init()
        manager.delegate = self
        manager.desiredAccuracy = kCLLocationAccuracyBest
        manager.allowsBackgroundLocationUpdates = true
        manager.pausesLocationUpdatesAutomatically = false
        manager.requestAlwaysAuthorization()
    }
    
    func startTracking() {
        manager.startUpdatingLocation()
    }
    
    func locationManager(_ manager: CLLocationManager, 
                        didUpdateLocations locations: [CLLocation]) {
        guard let location = locations.last else { return }
        
        // Send to API
        sendLocationUpdate(
            lat: location.coordinate.latitude,
            lon: location.coordinate.longitude,
            accuracy: location.horizontalAccuracy
        )
    }
}
```

5. **Implement API Client:**
```swift
import Foundation

class PhoneMonitorAPI {
    let baseURL = "https://phone-monitor.defecttracker.uk/api/"
    
    func registerDevice(deviceId: String, ownerName: String) async throws {
        let url = URL(string: baseURL + "register.php")!
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        
        let body: [String: Any] = [
            "device_id": deviceId,
            "owner_name": ownerName,
            "device_model": UIDevice.current.model,
            "os_version": UIDevice.current.systemVersion
        ]
        
        request.httpBody = try JSONSerialization.data(withJSONObject: body)
        
        let (data, response) = try await URLSession.shared.data(for: request)
        // Handle response...
    }
    
    func sendLocationUpdate(lat: Double, lon: Double, accuracy: Double) async throws {
        let url = URL(string: baseURL + "ping.php")!
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        
        let body: [String: Any] = [
            "device_id": UserDefaults.standard.string(forKey: "device_id") ?? "",
            "latitude": lat,
            "longitude": lon,
            "accuracy": accuracy,
            "battery_level": UIDevice.current.batteryLevel * 100,
            "timestamp": ISO8601DateFormatter().string(from: Date())
        ]
        
        request.httpBody = try JSONSerialization.data(withJSONObject: body)
        
        let (data, response) = try await URLSession.shared.data(for: request)
        // Handle response...
    }
}
```

6. **Add Persistent Notification:**
```swift
import UserNotifications

class NotificationManager {
    static let shared = NotificationManager()
    
    func showPersistentNotification() {
        let content = UNMutableNotificationContent()
        content.title = "PhoneMonitor Active"
        content.body = "Location tracking is enabled"
        content.sound = nil
        
        let request = UNNotificationRequest(
            identifier: "persistent",
            content: content,
            trigger: nil
        )
        
        UNUserNotificationCenter.current().add(request)
    }
}
```

7. **Build & Test:**
   - Connect iPhone to Mac
   - Select device in Xcode
   - Click Run (⌘R)
   - Grant location permissions
   - Test background tracking

**iOS Build Output:**
- Debug: .app bundle (for testing)
- Release: .ipa file (for distribution)
- App Store: Submit via App Store Connect

### Option 2: React Native (Cross-Platform)

**Pros:**
- Single codebase for both platforms
- JavaScript/TypeScript
- Large community & libraries

**Cons:**
- Complete rewrite required
- Larger app size (~20-30 MB)
- Performance overhead

**Quick Start:**
```bash
npx react-native init PhoneMonitor
cd PhoneMonitor

# Add dependencies
npm install @react-native-community/geolocation
npm install @react-native-community/netinfo
npm install axios

# Run on Android
npm run android

# Run on iOS (Mac only)
npm run ios
```

### Option 3: Flutter (Cross-Platform)

**Pros:**
- Fast native performance
- Beautiful UI out of the box
- Hot reload for quick development

**Cons:**
- New language (Dart)
- Complete rewrite required
- Less mature ecosystem

**Quick Start:**
```bash
flutter create phonemonitor
cd phonemonitor

# Add dependencies to pubspec.yaml
dependencies:
  geolocator: ^10.1.0
  permission_handler: ^11.0.1
  http: ^1.1.0

# Run on Android
flutter run -d android

# Run on iOS (Mac only)
flutter run -d ios
```

---

## ⚙️ Configuration

### API Endpoint Configuration

**Android (Kotlin):**
```kotlin
// android/app/src/main/java/com/phonemonitor/app/Constants.kt
object Constants {
    const val API_BASE_URL = "https://your-domain.com/api/"
}
```

**iOS (Swift):**
```swift
// iOS/PhoneMonitor/Config.swift
struct Config {
    static let apiBaseURL = "https://your-domain.com/api/"
}
```

**React Native (JavaScript):**
```javascript
// src/config.js
export const API_BASE_URL = 'https://your-domain.com/api/';
```

**Flutter (Dart):**
```dart
// lib/config.dart
class Config {
  static const String apiBaseUrl = 'https://your-domain.com/api/';
}
```

### Location Update Frequency

**Android:**
```kotlin
const val LOCATION_UPDATE_INTERVAL = 15 * 60 * 1000L // 15 minutes
const val LOCATION_FASTEST_INTERVAL = 5 * 60 * 1000L // 5 minutes
```

**iOS:**
```swift
let locationUpdateInterval: TimeInterval = 15 * 60 // 15 minutes
```

### Battery Optimization

**Best Practices:**
- Use 15-minute intervals (good balance between accuracy and battery)
- Use "Balanced Power" accuracy instead of "High Accuracy"
- Stop updates when battery < 10%
- Reduce frequency when device is stationary

**Android Configuration:**
```kotlin
locationRequest = LocationRequest.create().apply {
    interval = 15 * 60 * 1000 // 15 minutes
    fastestInterval = 5 * 60 * 1000 // 5 minutes
    priority = LocationRequest.PRIORITY_BALANCED_POWER_ACCURACY
}
```

**iOS Configuration:**
```swift
manager.desiredAccuracy = kCLLocationAccuracyHundredMeters
manager.distanceFilter = 100 // Only update when moved 100m
manager.activityType = .otherNavigation
```

---

## 🧪 Testing

### Android Testing

**1. Test on Emulator:**
```bash
# Start emulator
emulator -avd Pixel_6_API_34

# Install app
adb install app-debug.apk

# Send mock location
adb emu geo fix -0.1278 51.5074 0 # London coordinates
```

**2. Test on Real Device:**
- Enable Developer Options (tap Build Number 7 times)
- Enable USB Debugging
- Connect via USB
- Click Run in Android Studio

**3. Test Background Tracking:**
- Install app
- Grant location permissions (choose "Always Allow")
- Press Home button
- Wait 15 minutes
- Check dashboard for location update

**4. Test Battery Scenarios:**
```bash
# Simulate low battery
adb shell dumpsys battery set level 10

# Reset battery
adb shell dumpsys battery reset
```

### iOS Testing

**1. Test on Simulator:**
- Xcode → Product → Run
- Debug → Location → Custom Location
- Enter test coordinates

**2. Test on Real Device:**
- Connect iPhone via USB
- Trust computer on iPhone
- Select device in Xcode
- Click Run

**3. Test Background Mode:**
- Xcode → Debug → Simulate Location
- Choose "City Run" or "City Bicycle Ride"
- Press Home button
- App should continue tracking

### API Testing

**Test Registration:**
```bash
curl -X POST https://phone-monitor.defecttracker.uk/api/register.php \
  -H "Content-Type: application/json" \
  -d '{
    "device_id": "test-device-123",
    "owner_name": "Test User",
    "device_model": "Android SDK",
    "os_version": "13"
  }'
```

**Test Location Update:**
```bash
curl -X POST https://phone-monitor.defecttracker.uk/api/ping.php \
  -H "Content-Type: application/json" \
  -d '{
    "device_id": "test-device-123",
    "latitude": 51.5074,
    "longitude": -0.1278,
    "accuracy": 20,
    "battery_level": 85,
    "storage_free": 5000
  }'
```

---

## 📦 Distribution

### Android Distribution Options

**1. Direct APK Distribution (Easiest)**
- Build signed release APK
- Upload to your website
- Users download and install
- ✅ No approval process
- ✅ Free
- ⚠️ Users must enable "Unknown Sources"

**2. Google Play Store (Recommended for Public Release)**
- Create Google Play Developer account ($25 one-time fee)
- Upload app bundle (.aab)
- Fill out store listing
- Submit for review (1-7 days)
- ✅ Trusted source
- ✅ Auto-updates
- ❌ Review process

**3. Enterprise Distribution**
- Upload to company server
- Use Mobile Device Management (MDM)
- Push to company devices
- Good for family/corporate use

**Build App Bundle for Play Store:**
```bash
cd android
./gradlew bundleRelease

# Output: android/app/build/outputs/bundle/release/app-release.aab
```

### iOS Distribution Options

**1. TestFlight (Beta Testing)**
- Upload to App Store Connect
- Invite testers via email
- Up to 10,000 beta testers
- ✅ Free
- ✅ Easy testing
- ❌ Still needs developer account

**2. App Store (Public Release)**
- Submit via App Store Connect
- Apple review process (1-7 days)
- ✅ Trusted source
- ✅ Auto-updates
- ❌ $99/year developer account
- ❌ Strict review guidelines

**3. Enterprise Distribution**
- Requires Apple Enterprise Developer account ($299/year)
- Distribute to organization only
- Not for public distribution

**4. Ad Hoc Distribution (Testing)**
- Register device UDIDs (up to 100 devices)
- Build with Ad Hoc provisioning profile
- Distribute via email/website
- Good for family testing

---

## 🔧 Troubleshooting

### Android Issues

**Problem: Gradle sync failed**
```
Solution:
- File → Invalidate Caches → Invalidate and Restart
- Delete .gradle folder in project root
- Sync again
```

**Problem: App crashes on launch**
```
Solution:
- Check logcat in Android Studio
- Common causes:
  - API URL not configured
  - Missing permissions in AndroidManifest.xml
  - Network security config for HTTP (use HTTPS!)
```

**Problem: Location not updating**
```
Solution:
- Check location permission is "Always Allow"
- Disable battery optimization for app
- Settings → Apps → PhoneMonitor → Battery → Unrestricted
```

**Problem: Background service killed**
```
Solution:
- Enable foreground service (persistent notification)
- Disable battery optimization
- Check manufacturer-specific settings (Xiaomi, Huawei, etc.)
```

**Problem: "Installation blocked" error**
```
Solution:
- Enable "Install Unknown Apps"
- Settings → Security → Install Unknown Apps
- Select your browser/file manager
- Toggle on
```

### iOS Issues

**Problem: "Developer Mode required"**
```
Solution (iOS 16+):
- Settings → Privacy & Security → Developer Mode
- Toggle on
- Restart device
```

**Problem: Location permission denied**
```
Solution:
- Settings → Privacy → Location Services
- Find PhoneMonitor
- Choose "Always"
```

**Problem: App paused in background**
```
Solution:
- Add "location" to UIBackgroundModes in Info.plist
- Set allowsBackgroundLocationUpdates = true
- Use significant location changes for power efficiency
```

**Problem: Certificate errors**
```
Solution:
- Xcode → Preferences → Accounts
- Sign in with Apple ID
- Select team
- Automatic signing enabled
```

### API Connection Issues

**Problem: Network error / Unable to connect**
```
Solution:
- Check API_BASE_URL is correct (must end with /)
- Ensure server is accessible
- Check CORS headers on server
- Test API with curl/Postman
```

**Problem: SSL certificate errors**
```
Solution:
- Use HTTPS (not HTTP)
- Ensure valid SSL certificate on server
- Don't disable certificate validation in production!
```

---

## 📊 Comparison: Android vs iOS vs Cross-Platform

| Feature | Android (Native) | iOS (Native) | React Native | Flutter |
|---------|-----------------|--------------|--------------|---------|
| **Development Time** | ✅ Ready now | ⚠️ Need to build | ⚠️ Full rewrite | ⚠️ Full rewrite |
| **Language** | Kotlin/Java | Swift/Obj-C | JavaScript | Dart |
| **Performance** | ⭐⭐⭐⭐⭐ Excellent | ⭐⭐⭐⭐⭐ Excellent | ⭐⭐⭐⭐ Good | ⭐⭐⭐⭐⭐ Excellent |
| **App Size** | 8-12 MB | 10-15 MB | 20-30 MB | 15-25 MB |
| **Battery Impact** | Low | Low | Medium | Low |
| **Maintenance** | 2 codebases | 2 codebases | 1 codebase | 1 codebase |
| **Learning Curve** | Medium | Medium | Low (if know JS) | Medium |
| **Community** | Huge | Huge | Large | Growing |
| **IDE** | Android Studio | Xcode | VS Code/Any | VS Code/Any |
| **Hot Reload** | ❌ No | ❌ No | ✅ Yes | ✅ Yes |
| **Native APIs** | ✅ Direct | ✅ Direct | ⚠️ Via bridges | ✅ Direct |
| **UI Quality** | Excellent | Excellent | Good | Excellent |

---

## 🎯 Recommendations

### For Your Current Situation:

**Immediate (This Week):**
1. ✅ **Use the Android app** - It's ready and works great
2. Upload Android APK to your server for download
3. Test with Android family members first

**Short-term (Next Month):**
1. If you have a Mac, create basic iOS app using Swift guide above
2. Or hire iOS developer (Fiverr/Upwork: $500-2000)
3. Both apps use same backend API (already built!)

**Long-term (3-6 Months):**
1. Consider React Native or Flutter for easier maintenance
2. Single codebase = less work in future
3. Easier to add features to both platforms

### Best Choice for Most Users:

**If you have Android devices only:**
- ✅ Use the existing Android app (ready now!)

**If you need iOS + Android:**
- 🥇 **Option 1:** Build simple native iOS app (1-2 weeks if you know Swift)
- 🥈 **Option 2:** Hire iOS developer ($500-2000)
- 🥉 **Option 3:** Rewrite in React Native/Flutter (4-8 weeks, but easier long-term)

**If you're a developer:**
- Know Swift? → Build native iOS app
- Know JavaScript? → Use React Native
- Want best performance? → Use Flutter
- Want fastest result? → Hire iOS developer

---

## 📚 Additional Resources

### Android Development
- Official Docs: https://developer.android.com/docs
- Kotlin Guide: https://kotlinlang.org/docs/home.html
- Location Services: https://developer.android.com/training/location
- Background Tasks: https://developer.android.com/guide/background

### iOS Development
- Official Docs: https://developer.apple.com/documentation/
- Swift Guide: https://docs.swift.org/swift-book/
- Core Location: https://developer.apple.com/documentation/corelocation
- Background Tasks: https://developer.apple.com/documentation/backgroundtasks

### Cross-Platform
- React Native: https://reactnative.dev/
- Flutter: https://flutter.dev/
- Comparison: https://www.youtube.com/watch?v=X8ipUgXH6jw

### Useful Tools
- Android Studio: https://developer.android.com/studio
- Xcode: https://developer.apple.com/xcode/
- Postman (API testing): https://www.postman.com/
- Firebase (push notifications): https://firebase.google.com/

---

## 🚀 Quick Start Commands

### Android Build (Ready Now!)
```bash
# Clone repo
git clone https://github.com/irlam/PhoneMonitor.git
cd PhoneMonitor/android

# Open in Android Studio and click Run
# Or build from command line:
./gradlew assembleRelease

# Output: android/app/build/outputs/apk/release/app-release.apk
```

### iOS Build (Requires Swift Development)
```bash
# See "iOS Build Guide" section above
# Requires Mac + Xcode + Swift code (not yet built)
```

### React Native (Full Rewrite)
```bash
npx react-native init PhoneMonitor
cd PhoneMonitor
npm install @react-native-community/geolocation axios
# Then implement app logic...
```

### Flutter (Full Rewrite)
```bash
flutter create phonemonitor
cd phonemonitor
flutter pub add geolocator http permission_handler
# Then implement app logic...
```

---

## ✅ Next Steps

1. **Test Android app** on your devices
2. **Decide on iOS strategy** (native/cross-platform/hire developer)
3. **Configure API URL** in app before building
4. **Create signing key** for production releases
5. **Test thoroughly** before distributing to family
6. **Set up analytics** (optional - Firebase, Google Analytics)

---

**Need Help?**
- Check existing code in `android/` folder
- Test API with curl commands above
- Review logs in Android Studio logcat
- Ask specific questions about implementation

**Good luck building your apps! 🚀**
