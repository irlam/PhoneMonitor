package com.example.phonemonitor

import android.content.Context
import java.util.UUID

object DeviceIdManager {
    private const val PREFS_NAME = "phone_monitor_prefs"
    private const val KEY_DEVICE_UUID = "device_uuid"
    private const val KEY_CONSENT_GIVEN = "consent_given"
    private const val KEY_SERVER_URL = "server_url"
    private const val KEY_DEVICE_NAME = "device_name"
    private const val KEY_OWNER_NAME = "owner_name"
    private const val KEY_LOCATION_ENABLED = "location_enabled"

    fun getDeviceUuid(context: Context): String {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        var uuid = prefs.getString(KEY_DEVICE_UUID, null)
        
        if (uuid == null) {
            uuid = UUID.randomUUID().toString()
            prefs.edit().putString(KEY_DEVICE_UUID, uuid).apply()
        }
        
        return uuid
    }

    fun hasConsentGiven(context: Context): Boolean {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        return prefs.getBoolean(KEY_CONSENT_GIVEN, false)
    }

    fun setConsentGiven(context: Context, given: Boolean) {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        prefs.edit().putBoolean(KEY_CONSENT_GIVEN, given).apply()
    }

    fun getServerUrl(context: Context): String {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        return prefs.getString(KEY_SERVER_URL, BuildConfig.DEFAULT_SERVER_URL) ?: BuildConfig.DEFAULT_SERVER_URL
    }

    fun setServerUrl(context: Context, url: String) {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        prefs.edit().putString(KEY_SERVER_URL, url).apply()
    }

    fun getDeviceName(context: Context): String {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        return prefs.getString(KEY_DEVICE_NAME, android.os.Build.MODEL) ?: android.os.Build.MODEL
    }

    fun setDeviceName(context: Context, name: String) {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        prefs.edit().putString(KEY_DEVICE_NAME, name).apply()
    }

    fun getOwnerName(context: Context): String {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        return prefs.getString(KEY_OWNER_NAME, "Family Member") ?: "Family Member"
    }

    fun setOwnerName(context: Context, name: String) {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        prefs.edit().putString(KEY_OWNER_NAME, name).apply()
    }

    fun isLocationEnabled(context: Context): Boolean {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        return prefs.getBoolean(KEY_LOCATION_ENABLED, false)
    }

    fun setLocationEnabled(context: Context, enabled: Boolean) {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        prefs.edit().putBoolean(KEY_LOCATION_ENABLED, enabled).apply()
    }
}
