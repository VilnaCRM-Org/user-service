import colorTheme from '@/components/UiColorTheme';
import { golos } from '@/config/Fonts/golos';

export default {
  wrapper: {
    [`@media (max-width: 968px)`]: {
      position: 'relative',
      zIndex: '5',
      marginTop: '20.125rem',
      padding: '2rem 1.5rem 0 1.5rem',
      borderRadius: '1.5rem 1.5rem 0 0',
      background: colorTheme.palette.white.main,
      boxShadow: '0px -15px 1.5rem 0px rgba(108, 120, 132, 0.25)',
    },
  },

  cardWrapper: {
    gap: '0.75rem',
    flexDirection: 'row',
    [`@media (max-width: 968px)`]: {
      flexDirection: 'column',
      gap: '1rem',
    },
  },

  cardItem: {
    zIndex: '4',
    width: '50%',
    flexDirection: 'row',
    alignItems: 'center',
    gap: '0.75rem',
    padding: '1.688rem 2rem',
    borderRadius: '0.75rem',
    border: `1px solid ${colorTheme.palette.grey500.main}`,
    background: colorTheme.palette.white.main,
    boxShadow: '0px 8px 1.688rem 0px rgba(49, 59, 67, 0.14)',
    '@media (max-width: 1130.98px)': {
      padding: '1.813rem 2rem ',
    },
    [`@media (max-width: 960px)`]: {
      border: 'none',
      boxShadow: 'none',
      flexDirection: 'row',
      padding: '0',
      width: '100%',
    },
  },

  secondTitle: {
    maxWidth: '23.313rem',
    pb: '2rem',
    fontFamily: golos.style.fontFamily,
    color: colorTheme.palette.darkPrimary.main,
    fontSize: '1.75rem',
    fontStyle: 'normal',
    fontWeight: '700',
    lineHeight: 'normal',
    pt: '9.7rem',
    '@media (max-width: 1130.98px)': {
      pt: '9.438rem',
    },
    [`@media (max-width: 968px)`]: {
      pb: '0.9375rem',
      maxWidth: '18.563rem',
      color: colorTheme.palette.darkPrimary.main,
      fontSize: '1.375rem',
      fontStyle: 'normal',
      fontWeight: '700',
      lineHeight: 'normal',
      letterSpacing: '0',
      pt: '0',
    },
  },

  optionText: {
    [`@media (max-width: 968px)`]: {
      color: colorTheme.palette.darkPrimary.main,
      fontSize: '0.9375rem',
      fontStyle: 'normal',
      fontWeight: '400',
      lineHeight: '1.563rem',
    },
  },

  button: {
    zIndex: 2,
    maxWidth: '8.563rem',
    display: 'none',
    mt: '2rem',
    [`@media (max-width: 968px)`]: {
      display: 'inline-block',
    },
  },

  img: {
    width: '1.5rem',
    height: '1.5rem',
    [`@media (max-width: 968px)`]: {
      width: '1.25rem',
      height: '1.25rem',
    },
  },
};
