package com.example.phonemonitor

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.content.Context
import android.content.pm.PackageManager
import android.location.Location
import android.os.BatteryManager
import android.os.Build
import android.os.Environment
import android.os.StatFs
import androidx.core.app.ActivityCompat
import androidx.core.app.NotificationCompat
import androidx.work.CoroutineWorker
import androidx.work.ForegroundInfo
import androidx.work.WorkerParameters
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.Priority
import com.google.android.gms.tasks.CancellationToken
import com.google.android.gms.tasks.CancellationTokenSource
import com.google.android.gms.tasks.OnTokenCanceledListener
import kotlinx.coroutines.tasks.await

class HeartbeatWorker(
    context: Context,
    params: WorkerParameters
) : CoroutineWorker(context, params) {

    companion object {
        private const val CHANNEL_ID = "family_sharing_channel"
        private const val NOTIFICATION_ID = 1
    }

    override suspend fun doWork(): Result {
        setForeground(createForegroundInfo())

        return try {
            val apiService = ApiService.create(DeviceIdManager.getServerUrl(applicationContext))

            val battery = getBatteryLevel()
            val freeStorage = getFreeStorage()
            var location: Location? = null

            // Get location if enabled and permission granted
            if (DeviceIdManager.isLocationEnabled(applicationContext)) {
                location = getCurrentLocation()
            }

            val response = apiService.ping(
                PingRequest(
                    deviceUuid = DeviceIdManager.getDeviceUuid(applicationContext),
                    battery = battery,
                    freeStorage = freeStorage,
                    note = null,
                    lat = location?.latitude,
                    lon = location?.longitude,
                    accuracy = location?.accuracy,
                    provider = location?.provider,
                    locTs = location?.time
                )
            )

            if (response.isSuccessful && response.body()?.success == true) {
                Result.success()
            } else {
                if (response.code() == 403 && response.body()?.error == "revoked") {
                    // Device revoked, stop worker
                    DeviceIdManager.setConsentGiven(applicationContext, false)
                    Result.failure()
                } else {
                    Result.retry()
                }
            }
        } catch (e: Exception) {
            android.util.Log.e("HeartbeatWorker", "Error sending ping", e)
            Result.retry()
        }
    }

    private fun createForegroundInfo(): ForegroundInfo {
        createNotificationChannel()

        val notification = NotificationCompat.Builder(applicationContext, CHANNEL_ID)
            .setContentTitle(applicationContext.getString(R.string.app_name))
            .setContentText(applicationContext.getString(R.string.sharing_active))
            .setSmallIcon(android.R.drawable.ic_dialog_info)
            .setOngoing(true)
            .setPriority(NotificationCompat.PRIORITY_LOW)
            .build()

        return ForegroundInfo(NOTIFICATION_ID, notification)
    }

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                CHANNEL_ID,
                "Family Sharing",
                NotificationManager.IMPORTANCE_LOW
            ).apply {
                description = "Shows when family sharing is active"
            }

            val notificationManager = applicationContext.getSystemService(Context.NOTIFICATION_SERVICE) 
                as NotificationManager
            notificationManager.createNotificationChannel(channel)
        }
    }

    private fun getBatteryLevel(): Int {
        val batteryManager = applicationContext.getSystemService(Context.BATTERY_SERVICE) as BatteryManager
        return batteryManager.getIntProperty(BatteryManager.BATTERY_PROPERTY_CAPACITY)
    }

    private fun getFreeStorage(): String {
        val stat = StatFs(Environment.getDataDirectory().path)
        val bytesAvailable = try {
            stat.blockSizeLong * stat.availableBlocksLong
        } catch (e: ArithmeticException) {
            return "999GB+" // Overflow case
        }
        val megabytes = bytesAvailable / (1024 * 1024)
        val gigabytes = megabytes / 1024

        return if (gigabytes > 0) {
            "${gigabytes}GB"
        } else {
            "${megabytes}MB"
        }
    }

    private suspend fun getCurrentLocation(): Location? {
        if (ActivityCompat.checkSelfPermission(
                applicationContext,
                Manifest.permission.ACCESS_FINE_LOCATION
            ) != PackageManager.PERMISSION_GRANTED
        ) {
            return null
        }

        return try {
            val fusedLocationClient = LocationServices.getFusedLocationProviderClient(applicationContext)
            
            val cancellationTokenSource = CancellationTokenSource()
            
            fusedLocationClient.getCurrentLocation(
                Priority.PRIORITY_BALANCED_POWER_ACCURACY,
                cancellationTokenSource.token
            ).await()
        } catch (e: Exception) {
            android.util.Log.e("HeartbeatWorker", "Error getting location", e)
            null
        }
    }
}
