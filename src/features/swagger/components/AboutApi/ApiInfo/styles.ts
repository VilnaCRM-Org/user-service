import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';
import { golos } from '@/config/Fonts/golos';

export default {
  apiInfoWrapper: {
    marginBottom: '1.25rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      marginBottom: '1.5rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      marginBottom: '0.75rem',
    },
  },
  apiHeader: {
    marginBottom: '0.625rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      marginBottom: '0.75rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      gap: '0.5rem',
    },
  },
  apiTitle: {
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      fontSize: '1.75rem',
    },
  },
  apiVersion: {
    fontFamily: 'Inter, sans-serif',
    padding: '0.5rem 1rem',
    backgroundColor: 'rgba(30, 174, 255, 0.1)',
    color: colorTheme.palette.primary.main,
    fontSize: '1.125rem',
    borderRadius: '0.5rem',
    lineHeight: '1',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      padding: '0.5rem',
      fontSize: '0.875rem',
      fontWeight: 500,
    },
  },
  apiUrlPart: {
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      fontWeight: 600,
      fontSize: '1.125rem',
    },
    [`@media (max-width: 50rem)`]: {
      fontSize: '1.063rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      fontSize: '0.875rem',
    },
  },
  fullUrl: {
    color: colorTheme.palette.primary.main,
    textDecoration: 'none',
    fontWeight: 500,
    cursor: 'pointer',
    fontSize: '0.9375rem',
    fontFamily: golos.style.fontFamily,
    [`@media (max-width: 1130px)`]: {
      fontSize: '0.9375rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      fontWeight: 600,
      fontSize: '1.125rem',
    },
    [`@media (max-width: 50rem)`]: {
      fontSize: '1.063rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      fontSize: '0.875rem',
    },
  },
  apiBaseUrl: {
    gap: '0.625rem',
    marginBottom: '0.75rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      flexDirection: 'column',
      alignItems: 'normal',
    },
  },
  apiSpecialKey: {
    display: 'inline-block',
    margin: '0',
    padding: '4px 12px 5px 12px',
    background: colorTheme.palette.backgroundGrey200.main,
    borderRadius: '0.5rem',
    border: `1px solid ${colorTheme.palette.brandGray.main}`,
    color: colorTheme.palette.primary.main,
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      fontSize: '1.125rem',
      fontWeight: 600,
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      fontSize: '0.938rem',
      fontWeight: 500,
    },
  },
  description: {
    maxWidth: '43.5rem',
    color: `${colorTheme.palette.grey250.main}`,
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      maxWidth: '31.625rem',
      fontSize: '0.938rem',
      lineHeight: '1.563rem',
    },
  },
  linkLineBreak: {
    display: 'none',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xs}px)`]: {
      display: 'inline',
    },
  },
};
