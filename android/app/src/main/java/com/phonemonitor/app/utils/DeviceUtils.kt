package com.phonemonitor.app.utils

import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.os.BatteryManager
import android.os.Environment
import android.os.StatFs

object DeviceUtils {
    
    /**
     * Get battery level percentage (0-100)
     */
    fun getBatteryLevel(context: Context): Int {
        val batteryStatus: Intent? = IntentFilter(Intent.ACTION_BATTERY_CHANGED).let { filter ->
            context.registerReceiver(null, filter)
        }
        
        val level = batteryStatus?.getIntExtra(BatteryManager.EXTRA_LEVEL, -1) ?: -1
        val scale = batteryStatus?.getIntExtra(BatteryManager.EXTRA_SCALE, -1) ?: -1
        
        return if (level >= 0 && scale > 0) {
            (level * 100 / scale)
        } else {
            -1
        }
    }
    
    /**
     * Get free storage in GB
     */
    fun getFreeStorageGB(): Float {
        return try {
            val stat = StatFs(Environment.getDataDirectory().path)
            val bytesAvailable = stat.availableBlocksLong * stat.blockSizeLong
            val gbAvailable = bytesAvailable / (1024f * 1024f * 1024f)
            String.format("%.2f", gbAvailable).toFloat()
        } catch (e: Exception) {
            -1f
        }
    }
}
