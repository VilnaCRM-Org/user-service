import crypto from 'k6/crypto';

export default class TotpUtils {
  constructor() {
    this.period = 30;
    this.digits = 6;
    this.base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  }

  generateCandidateCodes(secret, timestamp = Math.floor(Date.now() / 1000)) {
    const candidates = [];
    const windows = [0, -this.period, this.period];

    for (const offset of windows) {
      const candidate = this.generateCode(secret, timestamp + offset);
      if (!candidates.includes(candidate)) {
        candidates.push(candidate);
      }
    }

    return candidates;
  }

  generateCode(secret, timestamp = Math.floor(Date.now() / 1000)) {
    const key = this.decodeBase32(secret);
    const counter = Math.floor(timestamp / this.period);
    const message = this.counterToBytes(counter);
    const digestHex = crypto.hmac('sha1', key.buffer, message.buffer, 'hex');
    const digest = this.hexToBytes(digestHex);

    const offset = digest[digest.length - 1] & 0x0f;
    const binary =
      ((digest[offset] & 0x7f) << 24) |
      ((digest[offset + 1] & 0xff) << 16) |
      ((digest[offset + 2] & 0xff) << 8) |
      (digest[offset + 3] & 0xff);

    const code = binary % 10 ** this.digits;

    return String(code).padStart(this.digits, '0');
  }

  counterToBytes(counter) {
    const buffer = new Uint8Array(8);
    let current = counter;

    for (let index = 7; index >= 0; index -= 1) {
      buffer[index] = current & 0xff;
      current = Math.floor(current / 256);
    }

    return buffer;
  }

  decodeBase32(secret) {
    const normalized = secret.toUpperCase().replace(/=+$/g, '').replace(/\s+/g, '');

    let bits = '';
    for (const char of normalized) {
      const value = this.base32Chars.indexOf(char);
      if (value < 0) {
        throw new Error('Invalid base32 character in TOTP secret');
      }

      bits += value.toString(2).padStart(5, '0');
    }

    const bytes = [];
    for (let index = 0; index + 8 <= bits.length; index += 8) {
      bytes.push(parseInt(bits.slice(index, index + 8), 2));
    }

    return new Uint8Array(bytes);
  }

  hexToBytes(hex) {
    const bytes = new Uint8Array(hex.length / 2);

    for (let index = 0; index < hex.length; index += 2) {
      bytes[index / 2] = parseInt(hex.slice(index, index + 2), 16);
    }

    return bytes;
  }
}
