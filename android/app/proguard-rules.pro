# Add project specific ProGuard rules here.
-keepattributes Signature
-keepattributes *Annotation*
-keepattributes Exceptions
-keepattributes InnerClasses
-keepattributes EnclosingMethod

# Retrofit
-dontwarn retrofit2.**
-keep class retrofit2.** { *; }
-keepclasseswithmembers class * {
    @retrofit2.http.* <methods>;
}

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

# Kotlin coroutines – required so that R8 does not strip Continuation and
# related classes that Retrofit uses at runtime for suspend functions.
# allowobfuscation+allowshrinking lets R8 still optimise within the package.
-keep,allowobfuscation,allowshrinking class kotlin.coroutines.** { *; }
-keepnames class kotlinx.coroutines.internal.MainDispatcherFactory {}
-keepnames class kotlinx.coroutines.CoroutineExceptionHandler {}
# keepclasseswithmembers ensures the enclosing class is kept when it has
# volatile fields, making the rule effective without a separate class keep.
-keepclasseswithmembers class kotlinx.coroutines.** {
    volatile <fields>;
}
-keepclassmembers class kotlin.coroutines.SafeContinuation {
    volatile <fields>;
}
-dontwarn kotlinx.coroutines.**

# Retrofit suspend-function support requires Continuation to survive R8
-keep,allowobfuscation,allowshrinking interface retrofit2.Call
-keep,allowobfuscation,allowshrinking class retrofit2.Response
-keep,allowobfuscation,allowshrinking class kotlin.coroutines.Continuation
