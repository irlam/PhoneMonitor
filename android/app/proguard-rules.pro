# Add project specific ProGuard rules here.
-keepattributes Signature
-keepattributes *Annotation*
-keepattributes Exceptions
-keepattributes InnerClasses
-keepattributes EnclosingMethod

# Retrofit
-dontwarn retrofit2.**
-keep class retrofit2.** { *; }
# Keep annotation default values (e.g. retrofit2.http.Field.encoded).
-keepattributes AnnotationDefault
# Retain service method parameters when optimizing.
-keepclassmembers,allowshrinking,allowobfuscation interface * {
    @retrofit2.http.* <methods>;
}
# With R8 full mode, it sees no subtypes of Retrofit interfaces since they are
# created with a Proxy and replaces all potential values with null. Explicitly
# keeping the interfaces prevents this (unconditional keep – no allowshrinking).
-if interface * { @retrofit2.http.* <methods>; }
-keep,allowobfuscation interface <1>
# With R8 full mode generic signatures are stripped for classes that are not
# kept. Suspend functions are wrapped in continuations where the type argument
# is used.
-keep,allowobfuscation,allowshrinking class kotlin.coroutines.Continuation
# Preserve generic return-type signatures on Retrofit service interfaces when
# reachable (allowshrinking = apply only when item is live, not unconditional).
-if interface * { @retrofit2.http.* <methods>; }
-keep,allowobfuscation,allowshrinking interface <1>
# Guarded by a NoClassDefFoundError try/catch and only used when on the classpath.
-dontwarn kotlin.Unit
# Top-level functions that can only be used by Kotlin.
-dontwarn retrofit2.KotlinExtensions
-dontwarn retrofit2.KotlinExtensions$*

# OkHttp
-dontwarn okhttp3.**
-keep class okhttp3.** { *; }
-keep interface okhttp3.** { *; }

# OkIO (required by OkHttp at runtime)
-dontwarn okio.**
-keep class okio.** { *; }
-keep interface okio.** { *; }

# Suppress warnings from platform classes not present on Android
-dontwarn sun.misc.**
-dontwarn java.lang.invoke.**
-dontwarn javax.annotation.**

# Gson
-dontwarn com.google.gson.**
-keep class com.google.gson.** { *; }
-keep class * implements com.google.gson.TypeAdapterFactory
-keep class * implements com.google.gson.JsonSerializer
-keep class * implements com.google.gson.JsonDeserializer

# App network models (Retrofit + Gson serialization)
-keep class com.phonemonitor.app.network.** { *; }

# Keep data classes
-keep class com.phonemonitor.app.data.** { *; }

# WorkManager
-keep class * extends androidx.work.Worker
-keep class * extends androidx.work.ListenableWorker {
    public <init>(android.content.Context, androidx.work.WorkerParameters);
}
-keep class com.phonemonitor.app.worker.** { *; }
