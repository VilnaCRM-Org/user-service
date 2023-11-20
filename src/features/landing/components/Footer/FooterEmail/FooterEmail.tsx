import { useTranslation } from 'react-i18next';

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
  },
};

export default function FooterEmail() {
  const { t } = useTranslation();

  return (
    <CustomLink
      href="mailto:info@vilnacrm.com"
      style={{ ...styles.mainLink, textAlign: 'center' }}
    >
      {t('info@vilnacrm.com')}
    </CustomLink>
  );
}
