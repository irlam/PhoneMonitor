package com.phonemonitor.app.network

import com.google.gson.annotations.SerializedName

// API Request Models
data class RegisterRequest(
    @SerializedName("device_uuid") val deviceUuid: String,
    @SerializedName("display_name") val displayName: String,
    @SerializedName("owner_name") val ownerName: String,
    @SerializedName("consent") val consent: Boolean
)

data class PingRequest(
    @SerializedName("device_uuid") val deviceUuid: String,
    @SerializedName("battery") val battery: Int?,
    @SerializedName("free_storage") val freeStorage: Float?,
    @SerializedName("note") val note: String?,
    @SerializedName("lat") val lat: Double?,
    @SerializedName("lon") val lon: Double?,
    @SerializedName("accuracy") val accuracy: Float?,
    @SerializedName("provider") val provider: String?,
    @SerializedName("loc_ts") val locTs: Long?
)

data class UnregisterRequest(
    @SerializedName("device_uuid") val deviceUuid: String
)

// API Response Models
data class ApiResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String?,
    @SerializedName("error") val error: String?,
    @SerializedName("device_id") val deviceId: Int?,
    @SerializedName("timestamp") val timestamp: String?
)
