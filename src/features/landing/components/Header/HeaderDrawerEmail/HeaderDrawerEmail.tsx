import { Paper, Typography } from '@mui/material';
import Image from 'next/image';

import CustomLink from '@/components/ui/CustomLink/CustomLink';

const styles = {
  mainLink: {
    color: '#1B2327',
    textAlign: 'center',
    fontFamily: 'GolosText-Regular, sans-serif',
    fontSize: '15px',
    fontStyle: 'normal',
    fontWeight: '500',
    lineHeight: '18px',
    padding: '8px 16px',
    borderRadius: '8px',
    border: '1px solid #D0D4D8',
    background: '#FFF',
    display: 'flex',
    gap: '10px',
    alignItems: 'center',
    justifyContent: 'center',
    width: '100%',
  },
};

export default function HeaderDrawerEmail({ style }: { style?: React.CSSProperties }) {
  return (
    <CustomLink
      href="mailto:info@vilnacrm.com"
      style={{
        display: 'flex',
        justifyContent: 'center',
        alignItems: 'center',
        width: '100%',
        marginTop: '6px',
        ...style,
      }}
    >
      <Typography style={{ ...styles.mainLink, textAlign: 'center', padding: '19px 10px' }}>
        <Paper elevation={0} style={{ border: 'none' }}>
          <Image width={24} height={24} src="/assets/svg/header-drawer/at-sign.svg" alt="At sign" />
        </Paper>
        <Typography
          style={{
            color: '#1B2327',
            textAlign: 'center',
            fontFamily: 'GolosText-Regular, sans-serif',
            fontSize: '18px',
            fontStyle: 'normal',
            fontWeight: '600',
            lineHeight: 'normal',
          }}
        >
          info@vilnacrm.com
        </Typography>
      </Typography>
    </CustomLink>
  );
}

HeaderDrawerEmail.defaultProps = {
  style: {},
};
