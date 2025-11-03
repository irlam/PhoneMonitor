package com.phonemonitor.app.worker

import android.app.Notification
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.content.pm.ServiceInfo
import android.os.Build
import androidx.core.app.NotificationCompat
import androidx.work.*
import com.phonemonitor.app.PhoneMonitorApp
import com.phonemonitor.app.R
import com.phonemonitor.app.data.DevicePreferences
import com.phonemonitor.app.network.PingRequest
import com.phonemonitor.app.network.RetrofitClient
import com.phonemonitor.app.ui.MainActivity
import com.phonemonitor.app.utils.DeviceUtils
import com.phonemonitor.app.utils.LocationHelper
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.util.concurrent.TimeUnit

class HeartbeatWorker(
    context: Context,
    params: WorkerParameters
) : CoroutineWorker(context, params) {
    
    companion object {
        private const val WORK_NAME = "heartbeat_work"
        private const val NOTIFICATION_ID = 1001
        
        fun schedule(context: Context, intervalMinutes: Long = 30) {
            val constraints = Constraints.Builder()
                .setRequiredNetworkType(NetworkType.CONNECTED)
                .build()
            
            val workRequest = PeriodicWorkRequestBuilder<HeartbeatWorker>(
                intervalMinutes, TimeUnit.MINUTES,
                15, TimeUnit.MINUTES // Flex interval
            )
                .setConstraints(constraints)
                .setBackoffCriteria(
                    BackoffPolicy.EXPONENTIAL,
                    WorkRequest.MIN_BACKOFF_MILLIS,
                    TimeUnit.MILLISECONDS
                )
                .build()
            
            WorkManager.getInstance(context)
                .enqueueUniquePeriodicWork(
                    WORK_NAME,
                    ExistingPeriodicWorkPolicy.UPDATE,
                    workRequest
                )
        }
        
        fun cancel(context: Context) {
            WorkManager.getInstance(context).cancelUniqueWork(WORK_NAME)
        }
    }
    
    override suspend fun doWork(): Result = withContext(Dispatchers.IO) {
        val prefs = DevicePreferences(applicationContext)
        
        if (!prefs.consentGiven) {
            return@withContext Result.failure()
        }
        
        try {
            setForeground(createForegroundInfo())
            
            val battery = DeviceUtils.getBatteryLevel(applicationContext)
            val storage = DeviceUtils.getFreeStorageGB()
            
            var lat: Double? = null
            var lon: Double? = null
            var accuracy: Float? = null
            var provider: String? = null
            var locTs: Long? = null
            
            if (prefs.locationEnabled) {
                val locationHelper = LocationHelper(applicationContext)
                val location = locationHelper.getCurrentLocation()
                location?.let {
                    lat = it.latitude
                    lon = it.longitude
                    accuracy = it.accuracy
                    provider = it.provider
                    locTs = it.time
                }
            }
            
            val request = PingRequest(
                deviceUuid = prefs.deviceUuid,
                battery = battery.takeIf { it >= 0 },
                freeStorage = storage.takeIf { it >= 0 },
                note = null,
                lat = lat,
                lon = lon,
                accuracy = accuracy,
                provider = provider,
                locTs = locTs
            )
            
            val apiService = RetrofitClient.getApiService(prefs.serverUrl)
            val response = apiService.ping(request)
            
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success()
            } else {
                Result.retry()
            }
        } catch (e: Exception) {
            e.printStackTrace()
            Result.retry()
        }
    }
    
    override suspend fun getForegroundInfo(): ForegroundInfo {
        return createForegroundInfo()
    }
    
    private fun createForegroundInfo(): ForegroundInfo {
        val intent = Intent(applicationContext, MainActivity::class.java)
        val pendingIntent = PendingIntent.getActivity(
            applicationContext,
            0,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )
        
        val notification = NotificationCompat.Builder(
            applicationContext,
            PhoneMonitorApp.NOTIFICATION_CHANNEL_ID
        )
            .setContentTitle("Family Sharing Active")
            .setContentText("Device status is being shared with family")
            .setSmallIcon(R.drawable.ic_notification)
            .setOngoing(true)
            .setContentIntent(pendingIntent)
            .setCategory(NotificationCompat.CATEGORY_SERVICE)
            .setPriority(NotificationCompat.PRIORITY_LOW)
            .build()
        
        return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            ForegroundInfo(
                NOTIFICATION_ID,
                notification,
                ServiceInfo.FOREGROUND_SERVICE_TYPE_LOCATION
            )
        } else {
            ForegroundInfo(NOTIFICATION_ID, notification)
        }
    }
}
