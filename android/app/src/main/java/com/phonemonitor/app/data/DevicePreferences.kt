package com.phonemonitor.app.data

import android.content.Context
import android.content.SharedPreferences
import java.util.UUID

class DevicePreferences(context: Context) {
    
    private val prefs: SharedPreferences = context.getSharedPreferences(
        "phone_monitor_prefs",
        Context.MODE_PRIVATE
    )
    
    companion object {
        private const val KEY_DEVICE_UUID = "device_uuid"
        private const val KEY_CONSENT_GIVEN = "consent_given"
        private const val KEY_SERVER_URL = "server_url"
        private const val KEY_DISPLAY_NAME = "display_name"
        private const val KEY_OWNER_NAME = "owner_name"
        private const val KEY_LOCATION_ENABLED = "location_enabled"
        private const val KEY_HEARTBEAT_INTERVAL = "heartbeat_interval"
        
        private const val DEFAULT_SERVER_URL = "https://your-domain.example"
        private const val DEFAULT_HEARTBEAT_INTERVAL = 30L // minutes
    }
    
    var deviceUuid: String
        get() {
            var uuid = prefs.getString(KEY_DEVICE_UUID, null)
            if (uuid == null) {
                uuid = UUID.randomUUID().toString()
                prefs.edit().putString(KEY_DEVICE_UUID, uuid).apply()
            }
            return uuid
        }
        set(value) = prefs.edit().putString(KEY_DEVICE_UUID, value).apply()
    
    var consentGiven: Boolean
        get() = prefs.getBoolean(KEY_CONSENT_GIVEN, false)
        set(value) = prefs.edit().putBoolean(KEY_CONSENT_GIVEN, value).apply()
    
    var serverUrl: String
        get() = prefs.getString(KEY_SERVER_URL, DEFAULT_SERVER_URL) ?: DEFAULT_SERVER_URL
        set(value) = prefs.edit().putString(KEY_SERVER_URL, value).apply()
    
    var displayName: String
        get() = prefs.getString(KEY_DISPLAY_NAME, "") ?: ""
        set(value) = prefs.edit().putString(KEY_DISPLAY_NAME, value).apply()
    
    var ownerName: String
        get() = prefs.getString(KEY_OWNER_NAME, "") ?: ""
        set(value) = prefs.edit().putString(KEY_OWNER_NAME, value).apply()
    
    var locationEnabled: Boolean
        get() = prefs.getBoolean(KEY_LOCATION_ENABLED, false)
        set(value) = prefs.edit().putBoolean(KEY_LOCATION_ENABLED, value).apply()
    
    var heartbeatInterval: Long
        get() = prefs.getLong(KEY_HEARTBEAT_INTERVAL, DEFAULT_HEARTBEAT_INTERVAL)
        set(value) = prefs.edit().putLong(KEY_HEARTBEAT_INTERVAL, value).apply()
    
    fun clear() {
        prefs.edit().clear().apply()
    }
}
