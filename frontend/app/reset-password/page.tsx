"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import styles from "../login.module.css";
import api from "@/lib/api";

export default function ResetPasswordRequestPage() {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);
  const router = useRouter();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      await api.post('/reset-password', { email });
      setSuccess(true);
    } catch (err: any) {
      console.error('Reset password error:', err);
      if (err.response?.data?.message) {
        setError(err.response.data.message);
      } else if (err.message) {
        setError(err.message);
      } else {
        setError('An error occurred. Please try again.');
      }
    } finally {
      setLoading(false);
    }
  };

  if (success) {
    return (
      <div className={`${styles.container} min-h-screen flex items-center justify-center`}>
        <div className={styles.portalCard}>
          <div className="text-center mb-6">
            <h1 className="text-2xl font-bold">Check Your Email</h1>
            <p className={`${styles.mutedText} mt-2 text-sm`}>
              If an account with that email exists, a password reset link has been sent.
            </p>
          </div>
          <button
            onClick={() => router.push('/')}
            className={`${styles.button} w-full mt-2 px-3 py-2 rounded-md bg-orange-500 hover:bg-orange-600 font-bold text-white transition-opacity`}
          >
            Back to Login
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className={`${styles.container} min-h-screen flex items-center justify-center`}>
      <div className={styles.portalCard}>
        <div className="text-center mb-6">
          <h1 className="text-2xl font-bold">Reset Your Password</h1>
          <p className={`${styles.mutedText} mt-2 text-sm`}>
            Enter your email address and we'll send you a link to reset your password.
          </p>
        </div>

        {error && (
          <div className="mb-4 p-3 rounded-md bg-red-500/10 border border-red-500 text-red-500 text-sm">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="w-full flex flex-col gap-4">
          <label className="flex flex-col text-md">
            Email
            <input
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              disabled={loading}
              className={`${styles.input} w-full mt-2 px-3 py-2 rounded-md bg-[#0a2a4a] border border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400 disabled:opacity-50 disabled:cursor-not-allowed`}
              placeholder="Enter your email"
            />
          </label>

          <button
            type="submit"
            disabled={loading}
            className={`${styles.button} w-full mt-2 px-3 py-2 rounded-md bg-orange-500 hover:bg-orange-600 font-bold text-white disabled:opacity-50 disabled:cursor-not-allowed transition-opacity`}
          >
            {loading ? "Sending..." : "Send Reset Link"}
          </button>
        </form>

        <div className="mt-4 flex flex-col items-center gap-2 text-sm">
          <button
            onClick={() => router.push('/')}
            className={`${styles.mutedText} hover:underline`}
          >
            Back to Login
          </button>
        </div>
      </div>
    </div>
  );
}