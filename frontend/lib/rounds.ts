import { RoundOption } from "./types";

const configuredRoundCount = Number(process.env.NEXT_PUBLIC_ROUND_COUNT);
if (!Number.isInteger(configuredRoundCount) || configuredRoundCount < 1) {
  throw new Error("NEXT_PUBLIC_ROUND_COUNT must be a positive integer.");
}

export const CONFIGURED_ROUNDS: RoundOption[] = Array.from({ length: configuredRoundCount }, (_, index) => {
  const number = index + 1;
  return {
    id: `r${number}`,
    name: `Round ${number}`,
  };
});

export function labelForRoundId(roundId: string): string {
  const match = /^r(\d+)$/.exec(roundId);
  if (match) {
    return `Round ${match[1]}`;
  }
  return roundId;
}
