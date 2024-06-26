const withBundleAnalyzer = require('@next/bundle-analyzer')();
const withExportImages = require('next-export-optimize-images');
const LocalizationGenerator = require('./scripts/localizationGenerator');

/** @type {import('next').NextConfig} */

const nextConfig = withExportImages({
  output: 'export',
  reactStrictMode: true,
  swcMinify: true,

  webpack: config => {
    const localizationGenerator = new LocalizationGenerator();
    localizationGenerator.generateLocalizationFile();

    return config;
  },
});

module.exports = process.env.ANALYZE === 'true' ? withBundleAnalyzer(nextConfig) : nextConfig;
