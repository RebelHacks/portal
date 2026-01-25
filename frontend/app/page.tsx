"use client";

import { useState } from "react";
import styles from "./login.module.css";

export default function LoginPage() {
  const [mode, setMode] = useState<"login" | "register">("login");

  return (
    <div className={`${styles.container} min-h-screen flex items-center justify-center`}>
<div className={styles.portalCard}>        
        {/* Header */}
        <div className="text-center mb-6">
          <h1 className="text-2xl font-bold">
            {mode === "login" ? "Welcome back" : "Create an account"}
          </h1>
          <p className={`${styles.mutedText} mt-2 text-sm`}>
            {mode === "login"
              ? "Sign in to access the portal"
              : "Register with your email and password"}
          </p>
        </div>

        {/* Form */}
        <form className="w-full flex flex-col gap-4">
          <label className="flex flex-col text-md">
            Email
            <input
              type="email"
              required
              className={`${styles.input} w-full mt-2 px-3 py-2 rounded-md bg-[#0a2a4a] border border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400`}
              placeholder="Email"
            />
          </label>

          <label className="flex flex-col text-md">
            Password
            <input
              type="password"
              required
              className={`${styles.input} w-full mt-2 px-3 py-2 rounded-md bg-[#0a2a4a] border border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400`}
              placeholder="Password"
            />
          </label>

          <button
            type="submit"
            className={`${styles.button} w-full mt-2 px-3 py-2 rounded-md bg-orange-500 hover:bg-orange-600 font-bold text-white`}
          >
            {mode === "login" ? "Log In" : "Register"}
          </button>
        </form>

        {/* Actions */}
        <div className="mt-4 flex flex-col items-center gap-2 text-sm">
          {mode === "login" && (
            <button className={`${styles.mutedText} hover:underline`}>
              Forgot password?
            </button>
          )}
          <button
            onClick={() => setMode(mode === "login" ? "register" : "login")}
            className={`${styles.mutedText} hover:underline`}
          >
            {mode === "login"
              ? "Don’t have an account? Register"
              : "Already have an account? Log in"}
          </button>
        </div>
      </div>
    </div>
  );
}