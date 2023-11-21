import { Typography } from '@mui/material';
import React from 'react';

import CustomCheckbox from '@/components/ui/CustomCheckbox/CustomCheckbox';
import CustomLink from '@/components/ui/CustomLink/CustomLink';
import { CHECKBOX_PRIVACY_POLICY_ID } from '@/features/landing/types/sign-up/types';
import { useTranslation } from 'react-i18next';

interface ISignUpPrivacyPolicyProps {
  isCheckboxChecked: boolean;
  onPrivacyPolicyCheckboxChange: (checked: boolean) => void;
}

const styles = {
  typography: {
    color: '#404142',
    fontFamily: '"Inter-Regular", sans-serif',
    fontSize: '14px',
    fontStyle: 'normal',
    fontWeight: '500',
    lineHeight: '18px',
  },
};

export default function SignUpPrivacyPolicy({
  isCheckboxChecked,
  onPrivacyPolicyCheckboxChange,
}: ISignUpPrivacyPolicyProps) {
  const { t } = useTranslation();
  const handleCheckboxChange = (checked: boolean) => {
    onPrivacyPolicyCheckboxChange(checked);
  };

  const checkboxLabelJSX = (
    <Typography style={{ ...styles.typography }}>
      {t('I have read and accept')}{' '}
      <CustomLink href="/" target="_blank">
        {t('the Privacy Policy')}
      </CustomLink>{' '}
      {t('and')}{' '}
      <CustomLink href="/" target="_blank">
        {t('Use Policy')}
      </CustomLink>{' '}
      {t('of the VilnaCRM service')}
    </Typography>
  );

  return (
    <CustomCheckbox
      id={CHECKBOX_PRIVACY_POLICY_ID}
      checked={isCheckboxChecked}
      onChange={handleCheckboxChange}
    >
      {checkboxLabelJSX}
    </CustomCheckbox>
  );
}
