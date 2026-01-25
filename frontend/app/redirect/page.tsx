"use client";
import { redirect } from "next/navigation";

export default function RedirectPage() {

  return (
    <div className="min-h-screen flex flex-col relative">
      <h1 className="text-4xl font-bold text-center mt-10">Redirected!</h1>
    </div>
  );
}