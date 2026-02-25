import { IsNotEmpty, IsOptional, IsString, IsNumber, IsBoolean, IsObject } from 'class-validator';
import { ApiProperty } from '@nestjs/swagger';

export class CreateLookupDto {
  @ApiProperty({ example: 'city', description: 'city, bank, status, citizen, feeling, contact_type, etc.' })
  @IsNotEmpty()
  @IsString()
  type: string;

  @ApiProperty({ example: 'إربد' })
  @IsNotEmpty()
  @IsString()
  name: string;

  @ApiProperty({ required: false })
  @IsOptional()
  @IsString()
  nameEn?: string;

  @ApiProperty({ required: false })
  @IsOptional()
  @IsNumber()
  parentId?: number;

  @ApiProperty({ required: false })
  @IsOptional()
  @IsNumber()
  sortOrder?: number;

  @ApiProperty({ default: true, required: false })
  @IsOptional()
  @IsBoolean()
  isActive?: boolean;

  @ApiProperty({ required: false, description: 'بيانات إضافية حسب النوع' })
  @IsOptional()
  @IsObject()
  metadata?: Record<string, any>;
}

export class BulkCreateLookupDto {
  @ApiProperty({ example: 'city' })
  @IsNotEmpty()
  @IsString()
  type: string;

  @ApiProperty({ example: ['إربد', 'عمان', 'الزرقاء'] })
  @IsNotEmpty()
  @IsString({ each: true })
  names: string[];
}
