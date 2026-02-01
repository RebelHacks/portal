"use client";

import { useState } from "react";
import styles from "./login.module.css";

export default function LoginPage() {
  const [mode, setMode] = useState<"login" | "register">("login");
  const [isJudge, setIsJudge] = useState<boolean | null>(false);

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
          {mode === "login" ? (
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


            </form>
          ) : (
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
            
              <div className="flex flex-col text-md">
                <label className="mb-2">Confirm Password</label>
                <input
                  type="password"
                  required
                  className={`${styles.input} w-full mt-2 px-3 py-2 rounded-md bg-[#0a2a4a] border border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400`}
                  placeholder="Confirm Password"
                />
              </div>
              <div className="flex flex-col gap-3">
                <span className="text-md font-medium">Are you a judge?</span>
                <div className="flex gap-4">
                  {['Yes', 'No'].map((option) => (
                    <label key={option} className="flex items-center gap-2 cursor-pointer group">
                      <input 
                        type="radio" 
                        name="isJudge"
                        value={option}
                        checked={isJudge === (option === 'Yes')}
                        onChange={() => setIsJudge(option === 'Yes')}
                        className="w-4 h-4 accent-cyan-400"
                      />
                      <span className="group-hover:text-cyan-400 transition-colors">{option}</span>
                    </label>
                  ))}
                </div>
              </div>
                
              {!isJudge && (
                <>
                  <div className="flex flex-col text-md mb-4">
                    <label className="mb-2">What is your major?</label>
                    <input
                      type="text"
                      required
                      className={`${styles.input} w-full px-3 py-2 rounded-md bg-[#0a2a4a] border border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400`}
                      placeholder="Major"
                    />
                  </div>
              
                  <div className="flex flex-col text-md">
                    <div className="flex items-center gap-2 mb-2">
                      <label>Upload your unofficial transcript</label>
              
                      <div className="group relative">
                        <span className="cursor-help text-cyan-400 text-xs border border-cyan-400 rounded-full px-1.5">?</span>
                        <div className="absolute bottom-full left-0 mb-2 w-64 p-2 bg-slate-800 text-xs text-white rounded hidden group-hover:block z-50 shadow-lg border border-slate-700">
                          Used to verify UNLV/CSN enrollment and placement in either advanced or beginner track.
                        </div>
                      </div>
                    </div>
              
                    <input 
                      type="file" 
                      accept=".pdf"
                      className={`${styles.input} pt-1 w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-cyan-500 file:text-white hover:file:bg-cyan-600 cursor-pointer`} 
                    />
                  </div>
                </>
              )}
  
            </form>
          )}

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